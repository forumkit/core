<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Mail implements ExtenderInterface
{
    private $drivers = [];

    /**
     * 添加邮件驱动程序。
     *
     * @param string $identifier: 邮件驱动程序的标识符。例如，SmtpDriver的标识符为'smtp'
     * @param string $driver: 驱动程序类的::class属性，该类必须实现 Forumkit\Mail\DriverInterface.
     * @return self
     */
    public function driver(string $identifier, string $driver): self
    {
        $this->drivers[$identifier] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('mail.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
