<?php

namespace Forumkit\User\Event;

use Forumkit\Http\AccessToken;
use Forumkit\User\User;

class LoggedIn
{
    public $user;

    public $token;

    public function __construct(User $user, AccessToken $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
