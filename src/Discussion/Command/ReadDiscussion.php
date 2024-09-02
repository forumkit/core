<?php

namespace Forumkit\Discussion\Command;

use Forumkit\User\User;

class ReadDiscussion
{
    /**
     * The ID of the discussion to mark as read.
     *
     * @var int
     */
    public $discussionId;

    /**
     * The user to mark the discussion as read for.
     *
     * @var User
     */
    public $actor;

    /**
     * The number of the post to mark as read.
     *
     * @var int
     */
    public $lastReadPostNumber;

    /**
     * @param int $discussionId The ID of the discussion to mark as read.
     * @param User $actor The user to mark the discussion as read for.
     * @param int $lastReadPostNumber The number of the post to mark as read.
     */
    public function __construct($discussionId, User $actor, $lastReadPostNumber)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->lastReadPostNumber = $lastReadPostNumber;
    }
}
