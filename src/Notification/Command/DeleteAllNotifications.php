<?php

namespace Forumkit\Notification\Command;

use Forumkit\User\User;

class DeleteAllNotifications
{
    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * @param User $actor 执行该操作的用户
     */
    public function __construct(User $actor)
    {
        $this->actor = $actor;
    }
}
