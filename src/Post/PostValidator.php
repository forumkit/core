<?php

namespace Forumkit\Post;

use Forumkit\Foundation\AbstractValidator;

class PostValidator extends AbstractValidator
{
    protected $rules = [
        'content' => [
            'required',
            'max:65535'
        ]
    ];
}
