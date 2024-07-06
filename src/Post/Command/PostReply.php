<?php

namespace Forumkit\Post\Command;

use Forumkit\User\User;

class PostReply
{
    /**
     * 要回复的讨论的ID。
     *
     * @var int
     */
    public $discussionId;

    /**
     * 执行该操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 分配给新帖子的属性。
     *
     * @var array
     */
    public $data;

    /**
     * 操作者的IP地址。
     *
     * @var string
     */
    public $ipAddress;

    /**
     * @var bool
     */
    public $isFirstPost;

    /**
     * 构造函数
     * 
     * @param int $discussionId 要回复的讨论的ID
     * @param User $actor 执行该操作的用户
     * @param array $data 分配给新帖子的属性
     * @param string $ipAddress 操作者的IP地址（默认为null）
     * @param bool $isFirstPost 是否为第一个帖子（默认为false）
     */
    public function __construct($discussionId, User $actor, array $data, $ipAddress = null, bool $isFirstPost = false)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
        $this->ipAddress = $ipAddress;
        $this->isFirstPost = $isFirstPost;
    }
}
