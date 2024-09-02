<?php

namespace Forumkit\Discussion\Event;

use Forumkit\Discussion\UserState;

class UserDataSaving
{
    /**
     * @var \Forumkit\Discussion\UserState
     */
    public $state;

    /**
     * @param \Forumkit\Discussion\UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
