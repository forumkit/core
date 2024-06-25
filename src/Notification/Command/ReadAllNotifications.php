<?php

namespace Forumkit\Notification\Command;

use Forumkit\User\User;

class ReadAllNotifications
{
    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param User $actor The user performing the action.
     */
    public function __construct(User $actor)
    {
        $this->actor = $actor;
    }
}
