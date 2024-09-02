<?php

namespace Forumkit\Foundation;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

abstract class AbstractServiceProvider extends ServiceProvider
{
    /**
     * @deprecated perpetually, not removed because Laravel needs it.
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
