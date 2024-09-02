<?php

namespace Forumkit\Api\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class InvalidAccessTokenException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'invalid_access_token';
    }
}
