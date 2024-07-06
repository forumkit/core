<?php

namespace Forumkit\User;

use Forumkit\Foundation\Config;
use Forumkit\Settings\SettingsRepositoryInterface;
use SessionHandlerInterface;

interface SessionDriverInterface
{
    /**
     * 构建一个会话处理器以处理会话。
     * 设置和配置可以从Forumkit设置仓库中拉取或者从config.php文件中拉取。
     *
     * @param SettingsRepositoryInterface $settings: Forumkit 设置仓库的实例
     * @param Config $config: 围绕 `config.php` 的包装类实例
     */
    public function build(SettingsRepositoryInterface $settings, Config $config): SessionHandlerInterface;
}
