<?php

namespace Forumkit\Http\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class TokenMismatchException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'csrf_token_mismatch';
    }
}
