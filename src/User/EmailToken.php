<?php

namespace Forumkit\User;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\User\Exception\InvalidConfirmationTokenException;
use Illuminate\Support\Str;

/**
 * @property string $token
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property string $email
 */
class EmailToken extends AbstractModel
{
    /**
     * 应该被转换为日期的属性。
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
     * 为指定用户生成电子邮件令牌。
     *
     * @param string $email
     * @param int $userId
     *
     * @return static
     */
    public static function generate($email, $userId)
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->email = $email;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * 定义与此电子邮件令牌所有者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 查找具有给定ID的令牌，并断言它尚未过期。
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     * @return static
     * @throws InvalidConfirmationTokenException
     */
    public function scopeValidOrFail($query, $id)
    {
        /** @var static|null $token */
        $token = $query->find($id);

        if (! $token || $token->created_at->diffInDays() >= 1) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
