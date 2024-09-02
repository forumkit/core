<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\CommentPost;
use Forumkit\User\User;

class Restored
{
    /**
     * @var \Forumkit\Post\CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Forumkit\Post\CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
