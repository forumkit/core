<?php

namespace Forumkit\Discussion\Command;

use Forumkit\User\User;

class EditDiscussion
{
    /**
     * 要编辑的讨论的ID。
     *
     * @var int
     */
    public $discussionId;

    /**
     * 执行操作的用户。
     *
     * @var \Forumkit\User\User
     */
    public $actor;

    /**
     * 要更新的讨论的属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $discussionId 要编辑的讨论的ID
     * @param \Forumkit\User\User $actor 执行操作的用户
     * @param array $data 要更新的讨论的属性
     */
    public function __construct($discussionId, User $actor, array $data)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
