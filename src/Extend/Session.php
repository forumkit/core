<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Session implements ExtenderInterface
{
    private $drivers = [];

    /**
     * Register a new session driver.
     *
     * A driver can currently be selected by setting `session.driver` in `config.php`.
     *
     * @param string $name: The name of the driver.
     * @param string $driverClass: The ::class attribute of the driver.
     *                             Driver must implement `\Forumkit\User\SessionDriverInterface`.
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
