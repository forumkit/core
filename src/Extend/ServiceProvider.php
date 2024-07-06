<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class ServiceProvider implements ExtenderInterface
{
    private $providers = [];

    /**
     * 注册服务提供商。
     *
     * 服务提供商是一项高级功能，可能会提供对不具有向后兼容性的 Forumkit 内部的访问权限。
     *
     * @param string $serviceProviderClass 服务提供程序类的 ::class 属性。
     * @return self
     * @return自我
     */
    public function register(string $serviceProviderClass): self
    {
        $this->providers[] = $serviceProviderClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $app = $container->make('forumkit');

        foreach ($this->providers as $provider) {
            $app->register($provider);
        }
    }
}
