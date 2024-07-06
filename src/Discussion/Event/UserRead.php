<?php

namespace Forumkit\Discussion\Event;

use Forumkit\Discussion\UserState;
use Forumkit\User\User;

class UserRead
{
    /**
     * @var UserState
     */
    public $state;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
