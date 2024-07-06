<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\Post;
use Forumkit\User\User;

class Saving
{
    /**
     * 将要保存的帖子。
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
     * 要在帖子中更新的属性。
     *
     * @var array
     */
    public $data;

    /**
     * @param \Forumkit\Post\Post $post
     * @param User $actor
     * @param array $data
     */
    public function __construct(Post $post, User $actor, array $data = [])
    {
        $this->post = $post;
        $this->actor = $actor;
        $this->data = $data;
    }
}
