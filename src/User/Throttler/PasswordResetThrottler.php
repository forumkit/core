<?php

namespace Forumkit\User\Throttler;

use Carbon\Carbon;
use Forumkit\Http\RequestUtil;
use Forumkit\User\PasswordToken;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Logged-in users can request password reset email,
 * this throttler applies a timeout of 5 minutes between password resets.
 * This does not apply to guests requesting password resets.
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
