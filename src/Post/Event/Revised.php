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
     * 我们手动设置旧的内容，因为在这个阶段帖子已经用新内容更新了。
     * 所以原始内容不再可用。
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
