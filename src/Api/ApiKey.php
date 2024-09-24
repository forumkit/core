<?php

namespace Forumkit\Api;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\User\User;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $key
 * @property string|null $allowed_ips
 * @property string|null $scopes
 * @property int|null $user_id
 * @property \Forumkit\User\User|null $user
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $last_activity_at
 */
class ApiKey extends AbstractModel
{
    protected $dates = ['last_activity_at'];

    /**
     * 生成API密钥。
     *
     * @return static
     */
    public static function generate()
    {
        $key = new static;

        $key->key = Str::random(40);

        return $key;
    }

    public function touch()
    {
        $this->last_activity_at = Carbon::now();

        return $this->save();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
