<?php

namespace Forumkit\User\Command;

use Forumkit\User\User;

class EditUser
{
    /**
     * 要编辑的用户的ID。
     *
     * @var int
     */
    public $userId;

    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 要更新的用户属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $userId 要编辑的用户的ID
     * @param User $actor 执行操作的用户
     * @param array $data 要更新的用户属性
     */
    public function __construct($userId, User $actor, array $data)
    {
        $this->userId = $userId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
