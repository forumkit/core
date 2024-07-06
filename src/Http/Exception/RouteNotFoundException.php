<?php

namespace Forumkit\Http\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class RouteNotFoundException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'not_found';
    }
}
