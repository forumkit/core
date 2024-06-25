<?php

namespace Forumkit\Http\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class MethodNotAllowedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'method_not_allowed';
    }
}
