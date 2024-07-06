<?php

namespace Forumkit\User\Throttler;

use Carbon\Carbon;
use Forumkit\Http\RequestUtil;
use Forumkit\User\EmailToken;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 用户可以请求更改电子邮件，
 * 这个节流器在两次请求之间设置了5分钟的超时。
 */
class EmailChangeThrottler
{
    public static $timeout = 300;

    /**
     * @return bool|void
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if ($request->getAttribute('routeName') !== 'users.update') {
            return;
        }

        if (! Arr::has($request->getParsedBody(), 'data.attributes.email')) {
            return;
        }

        $actor = RequestUtil::getActor($request);

        // 检查最近是否已为用户创建了一个电子邮件令牌（最近5分钟内）。
        if (EmailToken::query()->where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }
    }
}
