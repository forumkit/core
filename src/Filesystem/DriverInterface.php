<?php

namespace Forumkit\Filesystem;

use Forumkit\Foundation\Config;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;

interface DriverInterface
{
    /**
     * 为此文件系统驱动程序构建一个 Laravel 云端文件系统。
     * 设置和配置可以从 Forumkit 设置仓库或 config.php 文件中获取。
     *
     * 通常，这是通过将 Flysystem 适配器包装在 Laravel 的 `Illuminate\Filesystem\FilesystemAdapter` 类中来完成的。
     * 你应该确保你使用的 Flysystem 适配器具有 `getUrl` 方法。
     * 如果没有，你应该创建一个子类来实现该方法。
     * 否则，这个驱动程序将不适用于像 `forumkit-assets` 或 `forumkit-avatars` 这样的面向公众的磁盘。
     *
     * @param string $diskName: 该驱动程序所使用的磁盘的名称，这通常用于定位特定于磁盘的设置
     * @param SettingsRepositoryInterface $settings: Forumkit 设置仓库的实例
     * @param Config $config: `config.php` 的包装类实例
     * @param array $localConfig: 如果这个磁盘使用的是 'local' 文件系统驱动程序，则会使用的配置数组。
     *                            其中一些设置可能是有用的（例如可见性）
     */
    public function build(string $diskName, SettingsRepositoryInterface $settings, Config $config, array $localConfig): Cloud;
}
