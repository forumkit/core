<?php

namespace Forumkit\User\Command;

use Forumkit\User\User;

class DeleteAvatar
{
    /**
     * The ID of the user to delete the avatar of.
     *
     * @var int
     */
    public $userId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $userId The ID of the user to delete the avatar of.
     * @param User $actor The user performing the action.
     */
    public function __construct($userId, User $actor)
    {
        $this->userId = $userId;
        $this->actor = $actor;
    }
}
