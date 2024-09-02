<?php

namespace Forumkit\User\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class InvalidConfirmationTokenException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'invalid_confirmation_token';
    }
}
