<?php

namespace Forumkit\Notification\Command;

use Forumkit\User\User;

class ReadNotification
{
    /**
     * 要标记为已读的通知的ID。
     *
     * @var int
     */
    public $notificationId;

    /**
     * 执行此操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $notificationId 要标记为已读的通知的ID
     * @param User $actor 执行此操作的用户
     */
    public function __construct($notificationId, User $actor)
    {
        $this->notificationId = $notificationId;
        $this->actor = $actor;
    }
}
