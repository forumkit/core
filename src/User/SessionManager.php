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
     * 返回已配置的会话处理器。
     * 使用 `config.php` 中的 `session.driver` 项来挑选驱动器。
     * 如果配置的驱动器不可用，将回退到默认驱动器，
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

            // 但我们将向网站管理员记录一个关键错误。
            $this->container->make(LoggerInterface::class)->critical(
                "The configured session driver [$driverName] is not available. Falling back to default [$defaultDriverName]. Please check your configuration."
            );
        }

        return $driverInstance->getHandler();
    }
}
