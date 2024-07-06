<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Filesystem implements ExtenderInterface
{
    private $disks = [];
    private $drivers = [];

    /**
     * 声明一个新的文件系统磁盘。
     * 磁盘代表存储位置，由存储驱动器支持。
     * Forumkit 核心使用磁盘来存储资源和头像。
     *
     * 默认情况下，将为磁盘使用 "local" 驱动器。
     * "local" 驱动器表示你的 Forumkit 安装程序运行的文件系统。
     *
     * 要声明一个新的磁盘，你必须为 "local" 驱动器提供默认配置。
     *
     * @param string $name: 磁盘的名称
     * @param string|callable $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     *  - \Forumkit\Foundation\Paths $paths
     *  - \Forumkit\Http\UrlGenerator $url
     *
     * 可调用函数应返回：
     * - Laravel 磁盘配置数组
     *   该数组中不需要 `driver` 键，并且将被忽略。
     *
     * @example
     * ```
     * ->disk('forumkit-uploads', function (Paths $paths, UrlGenerator $url) {
     *       return [
     *          'root'   => "$paths->public/assets/uploads",
     *          'url'    => $url->to('site')->path('assets/uploads')
     *       ];
     *   });
     * ```
     *
     * @see https://laravel.com/docs/8.x/filesystem#configuration
     *
     * @return self
     */
    public function disk(string $name, $callback): self
    {
        $this->disks[$name] = $callback;

        return $this;
    }

    /**
     * 注册一个新的文件系统驱动器。
     *
     * @param string $name: 驱动器的名称
     * @param string $driverClass: 驱动器的 ::class 属性。
     *                             驱动器必须实现 `\Forumkit\Filesystem\DriverInterface`
     * @return self
     */
    public function driver(string $name, string $driverClass): self
    {
        $this->drivers[$name] = $driverClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.filesystem.disks', function ($existingDisks) use ($container) {
            foreach ($this->disks as $name => $disk) {
                $existingDisks[$name] = ContainerUtil::wrapCallback($disk, $container);
            }

            return $existingDisks;
        });

        $container->extend('forumkit.filesystem.drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
