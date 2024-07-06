<?php

namespace Forumkit\Foundation;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

abstract class AbstractServiceProvider extends ServiceProvider
{
    /**
     * @deprecated 长期不推荐使用，但未移除是因为 Laravel 需要它。
     * @var Container
     */
    protected $app;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->app = $container;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
    }
}
