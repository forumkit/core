<?php

namespace Forumkit\Discussion\Command;

use Forumkit\User\User;

class StartDiscussion
{
    /**
     * 发起讨论的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 讨论的属性。
     *
     * @var array
     */
    public $data;

    /**
     * 发起者的当前IP地址。
     *
     * @var string
     */
    public $ipAddress;

    /**
     * @param User   $actor 发起讨论的用户
     * @param array  $data  讨论的属性
     * @param string $ipAddress 发起者的当前IP地址
     */
    public function __construct(User $actor, array $data, string $ipAddress)
    {
        $this->actor = $actor;
        $this->data = $data;
        $this->ipAddress = $ipAddress;
    }
}
