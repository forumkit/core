<?php

namespace Forumkit\User\Exception;

use Exception;
use Forumkit\Foundation\KnownError;

class PermissionDeniedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'permission_denied';
    }
}
