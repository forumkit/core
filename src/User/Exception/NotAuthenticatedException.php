<?php

namespace Forumkit\User\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class NotAuthenticatedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'not_authenticated';
    }
}
