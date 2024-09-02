<?php

namespace Forumkit\Http\Event;

use Forumkit\Http\AccessToken;

class DeveloperTokenCreated
{
    /**
     * @var AccessToken
     */
    public $token;

    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}
