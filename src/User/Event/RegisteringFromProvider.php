<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class RegisteringFromProvider
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $provider;

    /**
     * @var array
     */
    public $payload;

    /**
     * @param User $user
     * @param $provider
     * @param $payload
     */
    public function __construct(User $user, string $provider, array $payload)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->payload = $payload;
    }
}
