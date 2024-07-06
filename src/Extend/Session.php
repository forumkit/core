<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Session implements ExtenderInterface
{
    private $drivers = [];

    /**
     * 注册一个新的会话驱动。
     *
     * 当前可以通过在 `config.php` 中设置 `session.driver` 来选择驱动。
     *
     * @param string $name: 驱动的名称
     * @param string $driverClass: 驱动的 ::class 属性
     *                             驱动必须实现 `\Forumkit\User\SessionDriverInterface` 接口
     * @return self
     */
    public function driver(string $name, string $driverClass): self
    {
        $this->drivers[$name] = $driverClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.session.drivers', function ($drivers) {
            return array_merge($drivers, $this->drivers);
        });
    }
}
