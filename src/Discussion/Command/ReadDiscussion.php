<?php

namespace Forumkit\Discussion\Command;

use Forumkit\User\User;

class ReadDiscussion
{
    /**
     * 要标记为已读的讨论的ID。
     *
     * @var int
     */
    public $discussionId;

    /**
     * 将讨论标记为已读的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 要标记为已读的帖子的编号。
     *
     * @var int
     */
    public $lastReadPostNumber;

    /**
     * @param int $discussionId 要标记为已读的讨论的ID
     * @param User $actor 将讨论标记为已读的用户
     * @param int $lastReadPostNumber 要标记为已读的帖子的编号
     */
    public function __construct($discussionId, User $actor, $lastReadPostNumber)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->lastReadPostNumber = $lastReadPostNumber;
    }
}
