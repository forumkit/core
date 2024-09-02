<?php

namespace Forumkit\Post\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class FloodingException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'too_many_requests';
    }
}
