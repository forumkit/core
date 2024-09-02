<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class ServiceProvider implements ExtenderInterface
{
    private $providers = [];

    /**
     * Register a service provider.
     *
     * Service providers are an advanced feature and might give access to Forumkit internals that do not come with backward compatibility.
     * Please read our documentation about service providers for recommendations.
     * @see https://forumkit.cn/docs/extend/service-provider/
     *
     * @param string $serviceProviderClass The ::class attribute of the service provider class.
     * @return self
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
