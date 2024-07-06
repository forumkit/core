<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\Post;
use Forumkit\User\User;

class Deleted
{
    /**
     * @var \Forumkit\Post\Post
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Forumkit\Post\Post $post
     */
    public function __construct(Post $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
