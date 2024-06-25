<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\Post;
use Forumkit\User\User;

class Saving
{
    /**
     * The post that will be saved.
     *
     * @var \Forumkit\Post\Post
     */
    public $post;

    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the post.
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
