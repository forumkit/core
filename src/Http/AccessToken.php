<?php

namespace Forumkit\Http;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\Database\ScopeVisibilityTrait;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property int $id 访问令牌的ID
 * @property string $token 访问令牌的值
 * @property int $user_id  用户的ID
 * @property Carbon $created_at 访问令牌的创建时间
 * @property Carbon|null $last_activity_at 最后一次活动的时间（可为空）
 * @property string $type 访问令牌的类型
 * @property string $title 访问令牌的标题（注：此属性在原始代码中未明确给出，可能是自定义扩展属性）
 * @property string|null $last_ip_address 最后一次活动时的IP地址（可为空）
 * @property string|null $last_user_agent 最后一次活动时的用户代理（可为空）
 * @property \Forumkit\User\User|null $user 与此访问令牌关联的用户（可为空）
 */
class AccessToken extends AbstractModel
{
    use ScopeVisibilityTrait;

    protected $table = 'access_tokens';

    protected $dates = [
        'created_at',
        'last_activity_at',
    ];

    /**
     * 访问令牌类型到类的映射表
     * 用于根据 `type` 列的值映射到对应的类
     *
     * @var array
     */
    protected static $models = [];

    /**
     * 该访问令牌的类型，存储在访问令牌表中
     *
     * 应被子类覆盖，并设置要在数据库中存储的值
     * 这个值将用于将加载的模型实例映射到正确的子类型
     *
     * @var string
     */
    public static $type = '';

    /**
     * 从上次活动开始，此访问令牌应持续有效的时长
     * 这个值将用于有效性和过期检查
     * 
     * @var int 生命周期（秒）。0 表示它永远不会过期
     */
    protected static $lifetime = 0;

    /**
     * 在调用 `updateLastSeen()` 之前，与当前的 `last_activity_at` 属性值的差异
     * 如果超过这个时间差，才会更新数据库中的属性。单位为秒
     */
    private const LAST_ACTIVITY_UPDATE_DIFF = 90;

    /**
     * 为指定用户生成一个访问令牌
     *
     * @param int $userId 用户ID
     * @return static 返回当前类的实例
     */
    public static function generate($userId)
    {
        if (static::class === self::class) {
            throw new \Exception('不允许使用 AccessToken::generate() 请在子类之一上使用 `generate` 方法。');
        } else {
            $token = new static;
            $token->type = static::$type;
        }

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->created_at = Carbon::now();
        $token->last_activity_at = Carbon::now();
        $token->save();

        return $token;
    }

    /**
     * 更新令牌的最后一次使用时间。
     * 如果提供了请求对象，则还会记录IP地址和用户代理。
     * @param ServerRequestInterface|null $request 请求对象（可为空）
     * @return bool 布尔值（表示操作是否成功）
     */
    public function touch(ServerRequestInterface $request = null)
    {
        $now = Carbon::now();

        if ($this->last_activity_at === null || $this->last_activity_at->diffInSeconds($now) > AccessToken::LAST_ACTIVITY_UPDATE_DIFF) {
            $this->last_activity_at = $now;
        }

        if ($request) {
            $this->last_ip_address = $request->getAttribute('ipAddress');
            // 我们截断用户代理以使其适应数据库列
            // 长度是硬编码为列的长度
            // 看起来MySQL或Laravel已经截断了值，但我们还是选择自己来做以确保安全
            $agent = Arr::get($request->getServerParams(), 'HTTP_USER_AGENT');
            $this->last_user_agent = substr($agent ?? '', 0, 255);
        } else {
            // 如果没有提供请求，我们将值设置回null
            // 这样这些值总是与last_activity中记录的日期相匹配
            $this->last_ip_address = null;
            $this->last_user_agent = null;
        }

        return $this->save();
    }

    /**
     * 定义与此访问令牌所有者之间的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 过滤给定日期时，对于特定令牌类型有效的令牌。
     * 默认使用 static::$lifetime 值，子类可以覆盖。
     * @param Builder $query 查询构建器
     * @param Carbon $date 给定的日期
     */
    protected static function scopeValid(Builder $query, Carbon $date)
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '>', $date->clone()->subSeconds(static::$lifetime));
        }
    }

    /**
     * 过滤给定日期时，已过期并准备进行垃圾回收的令牌。
     * 默认使用 static::$lifetime 值，子类可以覆盖。
     * @param Builder $query 查询构建器
     * @param Carbon $date 给定的日期
     */
    protected static function scopeExpired(Builder $query, Carbon $date)
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '<', $date->clone()->subSeconds(static::$lifetime));
        } else {
            $query->whereRaw('FALSE');
        }
    }

    /**
     * 快捷方法，用于查找有效的令牌。
     * @param string $token 用户发送的令牌值。我们允许非字符串值如null，以便可以直接从请求中传入任何值
     * @return AccessToken|null 有效的令牌实例或null
     */
    public static function findValid($token): ?AccessToken
    {
        return static::query()->whereValid()->where('token', $token)->first();
    }

    /**
     * 这个查询作用域意在用于基本的AccessToken对象上，以查询任何类型的有效令牌。
     * @param Builder $query 查询构建器
     * @param Carbon|null $date 给定的日期（可为空）
     */
    public function scopeWhereValid(Builder $query, Carbon $date = null)
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $query->where(function (Builder $query) use ($date) {
            foreach ($this->getModels() as $model) {
                $query->orWhere(function (Builder $query) use ($model, $date) {
                    $query->where('type', $model::$type);
                    $model::scopeValid($query, $date);
                });
            }
        });
    }

    /**
     * 这个查询作用域意在用于基本的AccessToken对象上，以查询任何类型的过期令牌。
     * @param Builder $query 查询构建器
     * @param Carbon|null $date 给定的日期（可为空）
     */
    public function scopeWhereExpired(Builder $query, Carbon $date = null)
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $query->where(function (Builder $query) use ($date) {
            foreach ($this->getModels() as $model) {
                $query->orWhere(function (Builder $query) use ($model, $date) {
                    $query->where('type', $model::$type);
                    $model::scopeExpired($query, $date);
                });
            }
        });
    }

    /**
     * 根据访问令牌类型创建新的模型实例。
     *
     * @param array $attributes 属性数组
     * @param string|null $connection 连接名称（可为空）
     * @return static|object 对应的模型实例或对象
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;

        if (! empty($attributes['type'])
            && isset(static::$models[$attributes['type']])
            && class_exists($class = static::$models[$attributes['type']])
        ) {
            /** @var AccessToken $instance */
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
     * @return array 类型到模型的映射数组
     */
    public static function getModels()
    {
        return static::$models;
    }

    /**
     * 为给定的访问令牌类型设置模型。
     *
     * @param string $type 访问令牌类型
     * @param string $model 该类型对应的模型类名
     * @return void 无返回值
     */
    public static function setModel(string $type, string $model)
    {
        static::$models[$type] = $model;
    }
}
