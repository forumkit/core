<?php

namespace Forumkit\User\Throttler;

use Carbon\Carbon;
use Forumkit\Http\RequestUtil;
use Forumkit\User\PasswordToken;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 已登录的用户可以请求密码重置电子邮件，
 * 这个节流器在两次密码重置之间设置了5分钟的超时。
 * 这不适用于请求密码重置的访客。
 */
class PasswordResetThrottler
{
    public static $timeout = 300;

    /**
     * @return bool|void
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if ($request->getAttribute('routeName') !== 'forgot') {
            return;
        }

        if (! Arr::has($request->getParsedBody(), 'email')) {
            return;
        }

        $actor = RequestUtil::getActor($request);

        if (PasswordToken::query()->where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }
    }
}
