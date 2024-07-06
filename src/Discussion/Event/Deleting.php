<?php

namespace Forumkit\Discussion\Event;

use Forumkit\Discussion\Discussion;
use Forumkit\User\User;

class Deleting
{
    /**
     * 将要被删除的讨论。
     *
     * @var Discussion
     */
    public $discussion;

    /**
     * 执行该操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与该命令相关的任何用户输入。
     *
     * @var array
     */
    public $data;

    /**
     * @param Discussion $discussion
     * @param User $actor
     * @param array $data
     */
    public function __construct(Discussion $discussion, User $actor, array $data = [])
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
        $this->data = $data;
    }
}
