<?php

namespace Forumkit\Notification\Driver;

use Forumkit\Notification\Blueprint\BlueprintInterface;
use Forumkit\User\User;

interface NotificationDriverInterface
{
    /**
     * 有条件地向用户发送通知，通常使用队列。
     *
     * @param BlueprintInterface $blueprint
     * @param User[] $users
     * @return void
     */
    public function send(BlueprintInterface $blueprint, array $users): void;

    /**
     * 注册通知类型的逻辑，通常用于添加用户偏好设置。
     *
     * @param string $blueprintClass
     * @param array $driversEnabledByDefault
     * @return void
     */
    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void;
}
