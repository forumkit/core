<?php

namespace Forumkit\Post\Command;

use Forumkit\User\User;

class DeletePost
{
    /**
     * 要删除的帖子的ID。
     *
     * @var int
     */
    public $postId;

    /**
     * 执行该操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 与该操作相关的任何其他用户输入。默认情况下不使用，但可能会被扩展使用。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $postId 要删除的帖子的ID
     * @param User $actor 执行该操作的用户
     * @param array $data 与该操作相关的任何其他用户输入。默认情况下不使用，但可能会被扩展使用
     */
    public function __construct($postId, User $actor, array $data = [])
    {
        $this->postId = $postId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
