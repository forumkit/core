<?php

namespace Forumkit\Api;

use Forumkit\Foundation\AbstractValidator;

class ForgotPasswordValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'email' => ['required', 'email']
    ];
}
