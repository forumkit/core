<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\User\User as ForumkitUser;
use Illuminate\Contracts\Container\Container;

class User implements ExtenderInterface
{
    private $displayNameDrivers = [];
    private $groupProcessors = [];
    private $preferences = [];

    /**
     * 添加一个显示名称驱动器。
     *
     * @param string $identifier: 显示名称驱动器的标识符。例如，对于 UserNameDriver，标识符为 'username'
     * @param string $driver: 驱动器类的 ::class 属性，该类必须实现 Forumkit\User\DisplayName\DriverInterface
     * @return self
     */
    public function displayNameDriver(string $identifier, string $driver): self
    {
        $this->displayNameDrivers[$identifier] = $driver;

        return $this;
    }

    /**
     * 在计算权限时动态处理用户的组列表。
     * 这可以根据上下文为用户赋予他们实际不在其中的组的权限。
     * 它不会更改为用户显示的组徽章。
     *
     * @param callable|string $callback 可调用的回调函数或类名
     *
     * 可调用的回调函数可以是一个闭包或可调用类，并且应该接受以下参数：
     * - \Forumkit\User\User $user: 相关的用户
     * - array $groupIds: 用户所属的组的ID数组
     *
     * 可调用的回调函数应该返回：
     * - array $groupIds: 用户所属的组的ID数组
     *
     * @return self
     */
    public function permissionGroups($callback): self
    {
        $this->groupProcessors[] = $callback;

        return $this;
    }

    /**
     * 注册新的用户首选项。
     *
     * @param string $key
     * @param callable $transformer
     * @param mixed|null $default
     * @return self
     */
    public function registerPreference(string $key, callable $transformer = null, $default = null): self
    {
        $this->preferences[$key] = compact('transformer', 'default');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.user.display_name.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->displayNameDrivers);
        });

        $container->extend('forumkit.user.group_processors', function ($existingRelations) {
            return array_merge($existingRelations, $this->groupProcessors);
        });

        foreach ($this->preferences as $key => $preference) {
            ForumkitUser::registerPreference($key, $preference['transformer'], $preference['default']);
        }
    }
}
