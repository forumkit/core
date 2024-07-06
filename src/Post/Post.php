<?php

namespace Forumkit\Post;

use Forumkit\Database\AbstractModel;
use Forumkit\Database\ScopeVisibilityTrait;
use Forumkit\Discussion\Discussion;
use Forumkit\Foundation\EventGeneratorTrait;
use Forumkit\Notification\Notification;
use Forumkit\Post\Event\Deleted;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

/**
 * 类属性说明
 * 
 * @property int $id 帖子ID
 * @property int $discussion_id 讨论ID
 * @property int|Expression $number 帖子编号或表达式
 * @property \Carbon\Carbon $created_at 帖子创建时间
 * @property int|null $user_id 发帖用户ID
 * @property string|null $type 帖子类型
 * @property string|null $content 帖子内容
 * @property \Carbon\Carbon|null $edited_at 帖子编辑时间
 * @property int|null $edited_user_id 编辑帖子用户ID
 * @property \Carbon\Carbon|null $hidden_at 帖子隐藏时间
 * @property int|null $hidden_user_id 隐藏帖子用户ID
 * @property \Forumkit\Discussion\Discussion|null $discussion 帖子所属讨论
 * @property User|null $user 发帖用户
 * @property User|null $editedUser 编辑帖子用户
 * @property User|null $hiddenUser 隐藏帖子用户
 * @property string $ip_address 发帖IP地址
 * @property bool $is_private 帖子是否为私有
 */
class Post extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasEagerLimit;

    protected $table = 'posts';

    /**
     * 应被转换为日期的属性。
     *
     * @var array
     */
    protected $dates = ['created_at', 'edited_at', 'hidden_at'];

    /**
     * 应被转换为原生类型的属性。
     *
     * @var array
     */
    protected $casts = [
        'is_private' => 'boolean'
    ];

    /**
     * 帖子类型与类的映射关系。
     *
     * @var array
     */
    protected static $models = [];

    /**
     * 存储在帖子表中的帖子类型。
     *
     * 应被子类重写，值为存储在数据库中的值，用于将加载的模型实例映射到正确的子类型。
     *
     * @var string
     */
    public static $type = '';

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        // 当帖子被创建时，根据子类的值设置其类型。
        // 并在讨论中为其分配一个自动递增的编号。
        static::creating(function (self $post) {
            $post->type = $post::$type;

            $db = static::getConnectionResolver()->connection();

            $post->number = new Expression('('.
                $db->table('posts', 'pn')
                    ->whereRaw($db->getTablePrefix().'pn.discussion_id = '.intval($post->discussion_id))
                    // IFNULL 仅适用于 MySQL/MariaDB
                    ->selectRaw('IFNULL(MAX('.$db->getTablePrefix().'pn.number), 0) + 1')
                    ->toSql()
            .')');
        });

        static::created(function (self $post) {
            $post->refresh();
            $post->discussion->save();
        });

        static::deleted(function (self $post) {
            $post->raise(new Deleted($post));

            Notification::whereSubject($post)->delete();
        });

        static::addGlobalScope(new RegisteredTypesScope);
    }

    /**
     * 判断该帖子是否对给定用户可见。
     *
     * @param User $user 用户实例
     * @return bool
     */
    public function isVisibleTo(User $user)
    {
        return (bool) $this->newQuery()->whereVisibleTo($user)->find($this->id);
    }

    /**
     * 定义帖子与讨论的关联关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    /**
     * 定义帖子与作者的关联关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 定义帖子与编辑该帖子的用户之间的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function editedUser()
    {
        return $this->belongsTo(User::class, 'edited_user_id');
    }

    /**
     * 定义帖子与隐藏该帖子的用户之间的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hiddenUser()
    {
        return $this->belongsTo(User::class, 'hidden_user_id');
    }

    /**
     * 移除应用于该模型的 `RegisteredTypesScope` 全局作用域约束，以获取所有帖子，无论其类型如何。
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAllTypes(Builder $query)
    {
        return $query->withoutGlobalScopes();
    }

    /**
     * 根据帖子的类型创建新的模型实例。
     *
     * @param array $attributes
     * @param string|null $connection
     * @return static|object
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;

        if (! empty($attributes['type'])
            && isset(static::$models[$attributes['type']])
            && class_exists($class = static::$models[$attributes['type']])
        ) {
            /** @var Post $instance */
            $instance = new $class;
            $instance->exists = true;
            $instance->setRawAttributes($attributes, true);
            $instance->setConnection($connection ?: $this->connection);

            return $instance;
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    /**
     * 获取类型到模型的映射。
     *
     * @return array
     */
    public static function getModels()
    {
        return static::$models;
    }

    /**
     * 为给定的帖子类型设置模型。
     *
     * @param string $type 帖子类型
     * @param string $model 该类型对应的模型类名
     * @return void
     *
     * @internal
     */
    public static function setModel(string $type, string $model)
    {
        static::$models[$type] = $model;
    }
}
