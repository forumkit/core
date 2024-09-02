<?php

namespace Forumkit\User\DisplayName;

use Forumkit\User\User;

/**
 * The default driver, which returns the user's username.
 */
class UsernameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return $user->username;
    }
}
