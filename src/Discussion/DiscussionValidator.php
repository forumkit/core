<?php

namespace Forumkit\Discussion;

use Forumkit\Foundation\AbstractValidator;

class DiscussionValidator extends AbstractValidator
{
    protected $rules = [
        'title' => [
            'required',
            'min:2',
            'max:80'
        ]
    ];
}
