<?php

namespace Forumkit\User\Command;

use Forumkit\User\User;

class DeleteUser
{
    /**
     * 要删除的用户的ID。
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
     * 与操作相关联的其他用户输入。默认情况下不使用，但可能会被扩展程序使用。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $userId 要删除的用户的ID
     * @param User $actor 执行操作的用户
     * @param array $data 与操作相关联的其他用户输入。默认情况下不使用，但可能会被扩展程序使用
     */
    public function __construct($userId, User $actor, array $data = [])
    {
        $this->userId = $userId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
