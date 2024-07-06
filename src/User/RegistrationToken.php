<?php

namespace Forumkit\User;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\User\Exception\InvalidConfirmationTokenException;
use Illuminate\Support\Str;

/**
 * @property string $token 令牌
 * @property string $provider 提供者
 * @property string $identifier 标识符
 * @property array $user_attributes  用户属性
 * @property array $payload 负载数据
 * @property \Carbon\Carbon $created_at 创建时间
 *
 * @method static self validOrFail(string $token) 验证令牌，如果无效则抛出异常
 */
class RegistrationToken extends AbstractModel
{
    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = ['created_at'];

    protected $casts = [
        'user_attributes' => 'array',
        'payload' => 'array'
    ];

    /**
     * 为此模型使用自定义主键
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $primaryKey = 'token';

    /**
     * 为指定用户生成认证令牌
     *
     * @param string $provider
     * @param string $identifier
     * @param array $attributes
     * @param array $payload
     * @return static
     */
    public static function generate(string $provider, string $identifier, array $attributes, array $payload)
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->provider = $provider;
        $token->identifier = $identifier;
        $token->user_attributes = $attributes;
        $token->payload = $payload;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * 查找具有给定ID的令牌，并断言它尚未过期
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $token
     *
     * @throws InvalidConfirmationTokenException
     *
     * @return RegistrationToken
     */
    public function scopeValidOrFail($query, string $token)
    {
        /** @var RegistrationToken|null $token */
        $token = $query->find($token);

        if (! $token || $token->created_at->lessThan(Carbon::now()->subDay())) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
