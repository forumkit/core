<?php

namespace Forumkit\User;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Illuminate\Support\Str;

/**
 * @property string $token
 * @property \Carbon\Carbon $created_at
 * @property int $user_id
 */
class PasswordToken extends AbstractModel
{
    /**
     * 应被转换为日期的属性。
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * 为此模型使用自定义主键。
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $primaryKey = 'token';

    /**
     * 为指定用户生成密码令牌。
     *
     * @param int $userId
     * @return static
     */
    public static function generate(int $userId)
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * 定义与此密码令牌所有者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
