<?php

namespace Forumkit\User\Command;

class ConfirmEmail
{
    /**
     * The email confirmation token.
     *
     * @var string
     */
    public $token;

    /**
     * @param string $token The email confirmation token.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }
}
