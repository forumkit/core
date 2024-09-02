<?php

namespace Forumkit\Http\Access;

use Forumkit\Http\AccessToken;
use Forumkit\User\Access\AbstractPolicy;
use Forumkit\User\User;

class AccessTokenPolicy extends AbstractPolicy
{
    public function revoke(User $actor, AccessToken $token)
    {
        if ($token->user_id === $actor->id || $actor->hasPermission('moderateAccessTokens')) {
            return $this->allow();
        }
    }
}
