<?php

namespace Forumkit\User\DisplayName;

use Forumkit\User\User;

/**
 * 默认驱动程序，返回用户的用户名。
 */
class UsernameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return $user->username;
    }
}
