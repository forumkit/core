<?php

namespace Forumkit\Group\Command;

use Forumkit\User\User;

class CreateGroup
{
    /**
     * 执行该操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 新组的属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param User $actor 执行该操作的用户
     * @param array $data 新组的属性
     */
    public function __construct(User $actor, array $data)
    {
        $this->actor = $actor;
        $this->data = $data;
    }
}
