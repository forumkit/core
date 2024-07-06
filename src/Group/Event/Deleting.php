<?php

namespace Forumkit\Group\Event;

use Forumkit\Group\Group;
use Forumkit\User\User;

class Deleting
{
    /**
     * 将被删除的组。
     *
     * @var Group
     */
    public $group;

    /**
     * 执行此操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与命令相关的任何用户输入。
     *
     * @var array
     */
    public $data;

    /**
     * @param Group $group 将被删除的组
     * @param User $actor 执行此操作的用户
     * @param array $data 与命令相关的任何用户输入
     */
    public function __construct(Group $group, User $actor, array $data)
    {
        $this->group = $group;
        $this->actor = $actor;
        $this->data = $data;
    }
}
