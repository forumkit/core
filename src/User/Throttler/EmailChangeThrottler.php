<?php

namespace Forumkit\User\Throttler;

use Carbon\Carbon;
use Forumkit\Http\RequestUtil;
use Forumkit\User\EmailToken;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Users can request an email change,
 * this throttler applies a timeout of 5 minutes between requests.
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

        // Check that an email token was not already created recently (last 5 minutes).
        if (EmailToken::query()->where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }
    }
}
