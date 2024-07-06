<?php

namespace Forumkit\Group\Command;

use Forumkit\User\User;

class EditGroup
{
    /**
     * 要编辑的组的ID。
     *
     * @var int
     */
    public $groupId;

    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 要在组上更新的属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $groupId 要编辑的组的ID
     * @param User $actor 执行操作的用户
     * @param array $data 要在组上更新的属性
     */
    public function __construct($groupId, User $actor, array $data)
    {
        $this->groupId = $groupId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
