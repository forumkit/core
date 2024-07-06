<?php

namespace Forumkit\Group\Command;

use Forumkit\User\User;

class DeleteGroup
{
    /**
     * 要删除的组的ID。
     *
     * @var int
     */
    public $groupId;

    /**
     * 执行该操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与该操作相关联的其他组输入。默认情况下不使用，但可能由扩展程序使用。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $groupId 要删除的组的ID
     * @param User $actor 执行该操作的用户
     * @param array $data 与该操作相关联的其他组输入。默认情况下不使用，但可能由扩展程序使用
     */
    public function __construct($groupId, User $actor, array $data = [])
    {
        $this->groupId = $groupId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
