<?php

namespace Forumkit\Discussion;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\Database\ScopeVisibilityTrait;
use Forumkit\Discussion\Event\Deleted;
use Forumkit\Discussion\Event\Hidden;
use Forumkit\Discussion\Event\Renamed;
use Forumkit\Discussion\Event\Restored;
use Forumkit\Discussion\Event\Started;
use Forumkit\Foundation\EventGeneratorTrait;
use Forumkit\Notification\Notification;
use Forumkit\Post\MergeableInterface;
use Forumkit\Post\Post;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $comment_count
 * @property int $participant_count
 * @property int $post_number_index !!DEPRECATED!!
 * @property \Carbon\Carbon $created_at
 * @property int|null $user_id
 * @property int|null $first_post_id
 * @property \Carbon\Carbon|null $last_posted_at
 * @property int|null $last_posted_user_id
 * @property int|null $last_post_id
 * @property int|null $last_post_number
 * @property \Carbon\Carbon|null $hidden_at
 * @property int|null $hidden_user_id
 * @property UserState|null $state
 * @property \Illuminate\Database\Eloquent\Collection $posts
 * @property \Illuminate\Database\Eloquent\Collection $comments
 * @property \Illuminate\Database\Eloquent\Collection $participants
 * @property Post|null $firstPost
 * @property User|null $user
 * @property Post|null $lastPost
 * @property User|null $lastPostedUser
 * @property \Illuminate\Database\Eloquent\Collection $readers
 * @property bool $is_private
 */
class Discussion extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * 在此次请求期间被修改的帖子数组。
     *
     * @var array
     */
    protected $modifiedPosts = [];

    /**
     * 应该被转换为日期的属性。
     *
     * @var array
     */
    protected $dates = ['created_at', 'last_posted_at', 'hidden_at'];

    /**
     * 应该被转换为原生类型的属性。
     *
     * @var array
     */
    protected $casts = [
        'is_private' => 'boolean'
    ];

    /**
     * 应该加载状态关系的用户。
     *
     * @var User|null
     */
    protected static $stateUser;

    /**
     * 启动模型。
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function (self $discussion) {
            Notification::whereSubjectModel(Post::class)
                ->whereIn('subject_id', function ($query) use ($discussion) {
                    $query->select('id')->from('posts')->where('discussion_id', $discussion->id);
                })
                ->delete();
        });

        static::deleted(function (self $discussion) {
            $discussion->raise(new Deleted($discussion));

            Notification::whereSubject($discussion)->delete();
        });
    }

    /**
     * 开始新的讨论。触发 DiscussionWasStarted 事件。
     *
     * @param string $title
     * @param User $user
     * @return static
     */
    public static function start($title, User $user)
    {
        $discussion = new static;

        $discussion->title = $title;
        $discussion->created_at = Carbon::now();
        $discussion->user_id = $user->id;

        $discussion->setRelation('user', $user);

        $discussion->raise(new Started($discussion));

        return $discussion;
    }

    /**
     * 重命名讨论。触发 DiscussionWasRenamed 事件。
     *
     * @param string $title
     * @return $this
     */
    public function rename($title)
    {
        if ($this->title !== $title) {
            $oldTitle = $this->title;
            $this->title = $title;

            $this->raise(new Renamed($this, $oldTitle));
        }

        return $this;
    }

    /**
     * 隐藏讨论。
     *
     * @param User $actor
     * @return $this
     */
    public function hide(User $actor = null)
    {
        if (! $this->hidden_at) {
            $this->hidden_at = Carbon::now();
            $this->hidden_user_id = $actor ? $actor->id : null;

            $this->raise(new Hidden($this));
        }

        return $this;
    }

    /**
     * 恢复讨论。
     *
     * @return $this
     */
    public function restore()
    {
        if ($this->hidden_at !== null) {
            $this->hidden_at = null;
            $this->hidden_user_id = null;

            $this->raise(new Restored($this));
        }

        return $this;
    }

    /**
     * 设置讨论的第一个帖子的详细信息。
     *
     * @param Post $post
     * @return $this
     */
    public function setFirstPost(Post $post)
    {
        $this->created_at = $post->created_at;
        $this->user_id = $post->user_id;
        $this->first_post_id = $post->id;

        return $this;
    }

    /**
     * 设置讨论的最新帖子详情。
     *
     * @param Post $post
     * @return $this
     */
    public function setLastPost(Post $post)
    {
        $this->last_posted_at = $post->created_at;
        $this->last_posted_user_id = $post->user_id;
        $this->last_post_id = $post->id;
        $this->last_post_number = $post->number;

        return $this;
    }

    /**
     * 刷新讨论的最新帖子详情。
     *
     * @return $this
     */
    public function refreshLastPost()
    {
        if ($lastPost = $this->comments()->latest()->first()) {
            /** @var Post $lastPost */
            $this->setLastPost($lastPost);
        }

        return $this;
    }

    /**
     * 刷新讨论的评论数量。
     *
     * @return $this
     */
    public function refreshCommentCount()
    {
        $this->comment_count = $this->comments()->count();

        return $this;
    }

    /**
     * 刷新讨论的参与者数量。
     *
     * @return $this
     */
    public function refreshParticipantCount()
    {
        $this->participant_count = $this->participants()->count('users.id');

        return $this;
    }

    /**
     * 保存帖子，尝试将其与讨论的最后一篇文章合并。
     *
     * 合并逻辑被委托给新帖子。 （例如，一个 DiscussionRenamedPost 如果与另一个相邻，则将合并 DiscussionRenamedPost 如果标题已恢复，则删除完全。
     *
     * @template T 的 \Forumkit\Post\MergeableInterface
     * @param T $post 保存的帖子。
     * @return T 生成的帖子。它可能与原本是要保存的。它也可能不存在，如果合并逻辑导致删除。
     */
    public function mergePost(MergeableInterface $post)
    {
        $lastPost = $this->posts()->latest()->first();

        $post = $post->saveAfter($lastPost);

        return $this->modifiedPosts[] = $post;
    }

    /**
     * 获取在此请求期间已修改的帖子。
     *
     * @return array
     */
    public function getModifiedPosts()
    {
        return $this->modifiedPosts;
    }

    /**
     * 定义与讨论帖子的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * 定义与讨论公开可见评论的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Post>
     */
    public function comments()
    {
        return $this->posts()
            ->where('is_private', false)
            ->whereNull('hidden_at')
            ->where('type', 'comment');
    }

    /**
     * 查询讨论的参与者（在讨论中发帖的唯一用户列表）。
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function participants()
    {
        return User::join('posts', 'posts.user_id', '=', 'users.id')
            ->where('posts.discussion_id', $this->id)
            ->where('posts.is_private', false)
            ->where('posts.type', 'comment')
            ->select('users.*')
            ->distinct();
    }

    /**
     * 定义与讨论的第一个帖子的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function firstPost()
    {
        return $this->belongsTo(Post::class, 'first_post_id');
    }

    /**
     * 定义与讨论的作者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 定义与讨论的最后一个帖子的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPost()
    {
        return $this->belongsTo(Post::class, 'last_post_id');
    }

    /**
     * 定义与讨论的最新发帖者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPostedUser()
    {
        return $this->belongsTo(User::class, 'last_posted_user_id');
    }

    /**
     * 定义与讨论的最相关帖子的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mostRelevantPost()
    {
        return $this->belongsTo(Post::class, 'most_relevant_post_id');
    }

    /**
     * 定义与讨论的读者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function readers()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * 定义与特定用户的讨论状态的关系。
     *
     * 如果没有传递用户（即在预先加载'state'关系的情况下），则使用静态的`$stateUser`属性。
     *
     * @param User|null $user
     * @return HasOne
     *
     * @see Discussion::setStateUser()
     */
    public function state(User $user = null): HasOne
    {
        $user = $user ?: static::$stateUser;

        return $this->hasOne(UserState::class)->where('user_id', $user ? $user->id : null);
    }

    /**
     * 获取用户的状态模型，如果不存在则实例化一个新的。
     */
    public function stateFor(User $user): UserState
    {
        /** @var UserState|null $state */
        $state = $this->state($user)->first();

        if (! $state) {
            $state = new UserState;
            $state->discussion_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    /**
     * 设置应该加载状态关系的用户。
     */
    public static function setStateUser(User $user)
    {
        static::$stateUser = $user;
    }

    /**
     * 设置讨论标题。
     *
     * 这会自动为讨论创建一个匹配的slug。
     */
    protected function setTitleAttribute(string $title)
    {
        $this->attributes['title'] = $title;
        $this->slug = Str::slug(
            $title,
            '-',
            resolve(SettingsRepositoryInterface::class)->get('default_locale', 'zh')
        );
    }
}
