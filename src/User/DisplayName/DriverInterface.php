<?php

namespace Forumkit\User\DisplayName;

use Forumkit\User\User;

/**
 * 显示名称驱动程序的接口。
 *
 * @public
 */
interface DriverInterface
{
    /**
     * 返回用户的显示名称。
     */
    public function displayName(User $user): string;
}
