<?php

namespace Forumkit\Discussion;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\Discussion\Event\UserRead;
use Forumkit\Foundation\EventGeneratorTrait;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * 在数据库中模拟讨论-用户状态记录。
 *
 * 存储关于用户已阅读讨论内容多少的信息。如果向数据库添加了适当的列，
 * 还可以用于存储其他信息，如用户对讨论的订阅状态。
 *
 * @property int $user_id
 * @property int $discussion_id
 * @property \Carbon\Carbon|null $last_read_at
 * @property int|null $last_read_post_number
 * @property Discussion $discussion
 * @property \Forumkit\User\User $user
 */
class UserState extends AbstractModel
{
    use EventGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'discussion_user';

    /**
     * 应该被转换为日期的属性。
     *
     * @var array
     */
    protected $dates = ['last_read_at'];

    /**
     * 可批量赋值的属性。
     *
     * @var string[]
     */
    protected $fillable = ['last_read_post_number'];

    /**
     * 将讨论标记为已阅读到某个点。触发 DiscussionWasRead 事件。
     *
     * @param int $number
     * @return $this
     */
    public function read($number)
    {
        if ($number > $this->last_read_post_number) {
            $this->last_read_post_number = $number;
            $this->last_read_at = Carbon::now();

            $this->raise(new UserRead($this));
        }

        return $this;
    }

    /**
     * 定义此状态所属讨论的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    /**
     * 定义此状态所属用户的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 设置保存更新查询的键。
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('discussion_id', $this->discussion_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
