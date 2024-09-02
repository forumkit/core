<?php

namespace Forumkit\Extension;

use Forumkit\Extension\Event\Disabling;
use Forumkit\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class ExtensionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton(ExtensionManager::class);
        $this->container->alias(ExtensionManager::class, 'forumkit.extensions');

        // Boot extensions when the app is booting. This must be done as a boot
        // listener on the app rather than in the service provider's boot method
        // below, so that extensions have a chance to register things on the
        // container before the core boots up (and starts resolving services).
        $this->container['forumkit']->booting(function () {
            $this->container->make('forumkit.extensions')->extend($this->container);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $events->listen(
            Disabling::class,
            DefaultLanguagePackGuard::class
        );
    }
}
