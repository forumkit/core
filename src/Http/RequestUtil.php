<?php

namespace Forumkit\Http;

use Forumkit\User\User;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequestUtil
{
    public static function getActor(Request $request): User
    {
        return $request->getAttribute('actorReference')->getActor();
    }

    public static function withActor(Request $request, User $actor): Request
    {
        $actorReference = $request->getAttribute('actorReference');

        if (! $actorReference) {
            $actorReference = new ActorReference;
            $request = $request->withAttribute('actorReference', $actorReference);
        }

        $actorReference->setActor($actor);

        // 在1.0版本中已被弃用
        $request = $request->withAttribute('actor', $actor);

        return $request;
    }
}
