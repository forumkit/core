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

        // 在应用启动时启动扩展。这必须作为应用的一个启动监听器来执行，而不是在服务提供商的 boot 方法中执行，
        // 这样扩展就有机会在应用核心启动（并开始解析服务）之前，在容器中注册一些东西。
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
