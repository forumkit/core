<?php

namespace Forumkit\Post\Event;

use Forumkit\Post\CommentPost;
use Forumkit\User\User;

class Revised
{
    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * We manually set the old content because at this stage the post
     * has already been updated with the new content. So the original
     * content is not available anymore.
     *
     * @var string
     */
    public $oldContent;

    public function __construct(CommentPost $post, User $actor, string $oldContent)
    {
        $this->post = $post;
        $this->actor = $actor;
        $this->oldContent = $oldContent;
    }
}
