<?php

namespace Forumkit\Post\Command;

use Forumkit\User\User;

class EditPost
{
    /**
     * 要编辑的帖子的ID。
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
     * 要在帖子上更新的属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param int $postId 要编辑的帖子的ID
     * @param User $actor 执行该操作的用户
     * @param array $data 要在帖子上更新的属性
     */
    public function __construct($postId, User $actor, array $data)
    {
        $this->postId = $postId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
