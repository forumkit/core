<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class AvatarDeleting
{
    /**
     * 头像将被删除的用户。
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
     * @param User $user 头像将被删除的用户
     * @param User $actor 执行操作的用户
     */
    public function __construct(User $user, User $actor)
    {
        $this->user = $user;
        $this->actor = $actor;
    }
}
