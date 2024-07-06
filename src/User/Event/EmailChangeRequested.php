<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class EmailChangeRequested
{
    /**
     * 请求更改电子邮件的用户。
     *
     * @var User
     */
    public $user;

    /**
     * 他们请求更改到的电子邮件地址。
     *
     * @var string
     */
    public $email;

    /**
     * @param User $user 请求更改电子邮件的用户
     * @param string $email 他们请求更改到的电子邮件地址
     */
    public function __construct(User $user, $email)
    {
        $this->user = $user;
        $this->email = $email;
    }
}
