<?php

namespace Forumkit\Http;

/**
 * @internal
 */
class RouteCollectionUrlGenerator
{
    /**
     * @var string|null
     */
    protected $baseUrl;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @param string $baseUrl
     * @param RouteCollection $routes
     */
    public function __construct($baseUrl, RouteCollection $routes)
    {
        $this->baseUrl = $baseUrl;
        $this->routes = $routes;
    }

    /**
     * 根据路由名称生成URL
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function route($name, $parameters = [])
    {
        $path = $this->routes->getPath($name, $parameters);
        $path = ltrim($path, '/');

        return $this->baseUrl.'/'.$path;
    }

    /**
     * 根据给定路径生成URL
     *
     * @param string $path
     * @return string
     */
    public function path($path)
    {
        return $this->baseUrl.'/'.$path;
    }

    /**
     * 生成带有UrlGenerator前缀的基础URL
     *
     * @return string
     */
    public function base()
    {
        return $this->baseUrl;
    }
}
