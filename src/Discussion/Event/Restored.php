<?php

namespace Forumkit\Discussion\Event;

use Forumkit\Discussion\Discussion;
use Forumkit\User\User;

class Restored
{
    /**
     * @var \Forumkit\Discussion\Discussion
     */
    public $discussion;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Forumkit\Discussion\Discussion $discussion
     * @param User $actor
     */
    public function __construct(Discussion $discussion, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
    }
}
