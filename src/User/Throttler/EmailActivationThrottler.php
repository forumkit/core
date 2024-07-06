<?php

namespace Forumkit\User\Throttler;

use Carbon\Carbon;
use Forumkit\Http\RequestUtil;
use Forumkit\User\EmailToken;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 未激活的用户可以请求一封确认邮件，
 * 此节流器在两次确认请求之间设置了5分钟的超时时间。
 */
class EmailActivationThrottler
{
    public static $timeout = 300;

    /**
     * @return bool|void
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if ($request->getAttribute('routeName') !== 'users.confirmation.send') {
            return;
        }

        $actor = RequestUtil::getActor($request);

        if (EmailToken::query()
            ->where('user_id', $actor->id)
            ->where('email', $actor->email)
            ->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))
            ->exists()) {
            return true;
        }
    }
}
