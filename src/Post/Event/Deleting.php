<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\Post;
use Forumkit\User\User;

class Deleting
{
    /**
     * The post that is going to be deleted.
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
     * Any user input associated with the command.
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
