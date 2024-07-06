<?php

namespace Forumkit\Group\Event;

use Forumkit\Group\Group;
use Forumkit\User\User;

class Saving
{
    /**
     * 将要保存的组。
     *
     * @var \Forumkit\Group\Group
     */
    public $group;

    /**
     * 执行此操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 将要在组上更新的属性
     *
     * @var array
     */
    public $data;

    /**
     * @param Group $group 将要保存的组
     * @param User $actor 执行此操作的用户
     * @param array $data 将要在组上更新的属性
     */
    public function __construct(Group $group, User $actor, array $data)
    {
        $this->group = $group;
        $this->actor = $actor;
        $this->data = $data;
    }
}
