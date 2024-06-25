<?php

namespace Forumkit\Http;

use Forumkit\Foundation\Application;

class UrlGenerator
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register a named route collection for URL generation.
     *
     * @param string $key
     * @param RouteCollection $routes
     * @param string $prefix
     * @return static
     */
    public function addCollection($key, RouteCollection $routes, $prefix = null)
    {
        $this->routes[$key] = new RouteCollectionUrlGenerator(
            $this->app->url($prefix),
            $routes
        );

        return $this;
    }

    /**
     * Retrieve an URL generator instance for the given named route collection.
     *
     * @param string $collection
     * @return RouteCollectionUrlGenerator
     */
    public function to($collection)
    {
        return $this->routes[$collection];
    }
}
