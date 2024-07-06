<?php

namespace Forumkit\Discussion\Event;

use Forumkit\Discussion\Discussion;
use Forumkit\User\User;

class Saving
{
    /**
     * 将被保存的讨论。
     *
     * @var \Forumkit\Discussion\Discussion
     */
    public $discussion;

    /**
     * 执行该操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与命令关联的任何用户输入。
     *
     * @var array
     */
    public $data;

    /**
     * @param \Forumkit\Discussion\Discussion $discussion
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
