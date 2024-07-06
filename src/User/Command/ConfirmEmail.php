<?php

namespace Forumkit\User\Command;

class ConfirmEmail
{
    /**
     * 电子邮件确认令牌。
     *
     * @var string
     */
    public $token;

    /**
     * @param string $token 电子邮件确认令牌
     */
    public function __construct($token)
    {
        $this->token = $token;
    }
}
