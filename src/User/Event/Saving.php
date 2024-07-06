<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class Saving
{
    /**
     * 要保存的用户。
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
     * 要更新的用户属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param User $user 要保存的用户
     * @param User $actor 执行操作的用户
     * @param array $data 要更新的用户属性
     */
    public function __construct(User $user, User $actor, array $data)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->data = $data;
    }
}
