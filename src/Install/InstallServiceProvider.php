<?php

namespace Forumkit\Install;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class InstallServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('forumkit.install.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, RouteHandlerFactory $route)
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'forumkit.install');

        $this->populateRoutes($container->make('forumkit.install.routes'), $route);
    }

    /**
     * @param RouteCollection     $routes
     * @param RouteHandlerFactory $route
     */
    protected function populateRoutes(RouteCollection $routes, RouteHandlerFactory $route)
    {
        $routes->get(
            '/{path:.*}',
            'index',
            $route->toController(Controller\IndexController::class)
        );

        $routes->post(
            '/{path:.*}',
            'install',
            $route->toController(Controller\InstallController::class)
        );
    }
}
