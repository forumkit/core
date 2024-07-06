<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\Post;
use Forumkit\User\User;

class Deleting
{
    /**
     * 将要被删除的帖子。
     *
     * @var \Forumkit\Post\Post
     */
    public $post;

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
     * @param \Forumkit\Post\Post $post
     * @param User $actor
     * @param array $data
     */
    public function __construct(Post $post, User $actor, array $data)
    {
        $this->post = $post;
        $this->actor = $actor;
        $this->data = $data;
    }
}
