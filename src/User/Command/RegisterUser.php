<?php

namespace Forumkit\User\Command;

use Forumkit\User\User;

class RegisterUser
{
    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 新用户的属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param User $actor 执行操作的用户
     * @param array $data 新用户的属性
     */
    public function __construct(User $actor, array $data)
    {
        $this->actor = $actor;
        $this->data = $data;
    }
}
