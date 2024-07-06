<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class Deleting
{
    /**
     * 将被删除的用户。
     *
     * @var User
     */
    public $user;

    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与该命令关联的任何用户输入。
     *
     * @var array
     */
    public $data;

    /**
     * @param User $user 将被删除的用户
     * @param User $actor 执行操作的用户
     * @param array $data 与该命令关联的任何用户输入
     */
    public function __construct(User $user, User $actor, array $data)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->data = $data;
    }
}
