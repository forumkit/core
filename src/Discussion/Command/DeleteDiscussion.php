<?php

namespace Forumkit\Discussion\Command;

use Forumkit\User\User;

class DeleteDiscussion
{
    /**
     * 要删除的讨论的ID。
     *
     * @var int
     */
    public $discussionId;

    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与该操作相关的任何其他用户输入。默认情况下不使用，但可能会被扩展程序使用。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $discussionId 要删除的讨论的ID
     * @param User $actor 执行操作的用户
     * @param array $data 与该操作相关的任何其他用户输入。默认情况下不使用，但可能会被扩展程序使用
     */
    public function __construct($discussionId, User $actor, array $data = [])
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
