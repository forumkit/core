<?php

namespace Forumkit\User;

use Forumkit\Foundation\Config;
use Illuminate\Session\SessionManager as IlluminateSessionManager;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

class SessionManager extends IlluminateSessionManager
{
    /**
     * 返回配置的会话处理器。
     * 从 `config.php` 中使用 `session.driver` 项获取驱动。
     * 如果配置的驱动不可用，则回退到默认驱动，
     * 并在此情况下记录一个关键错误。
     */
    public function handler(): SessionHandlerInterface
    {
        $config = $this->container->make(Config::class);
        $driverName = Arr::get($config, 'session.driver');

        try {
            $driverInstance = parent::driver($driverName);
        } catch (InvalidArgumentException $e) {
            $defaultDriverName = $this->getDefaultDriver();
            $driverInstance = parent::driver($defaultDriverName);

            // 但是，我们会向网站管理员记录一个关键错误
            $this->container->make(LoggerInterface::class)->critical(
                "配置的会话驱动 [$driverName] 不可用。回退到默认驱动 [$defaultDriverName]，请检查您的配置。"
            );
        }

        return $driverInstance->getHandler();
    }
}
