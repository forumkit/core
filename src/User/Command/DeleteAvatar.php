<?php

namespace Forumkit\User\Command;

use Forumkit\User\User;

class DeleteAvatar
{
    /**
     * 要删除头像的用户的ID
     *
     * @var int
     */
    public $userId;

    /**
     * 执行操作的用户
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $userId 要删除头像的用户的ID
     * @param User $actor 执行操作的用户
     */
    public function __construct($userId, User $actor)
    {
        $this->userId = $userId;
        $this->actor = $actor;
    }
}
