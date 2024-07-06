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
     * 为URL生成注册一个命名的路由集合。
     *
     * @param string $key 路由集合的键名
     * @param RouteCollection $routes 路由集合实例
     * @param string $prefix 可选的URL前缀
     * @return static 返回当前实例（通常用于链式调用）
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
     * 检索给定命名路由集合的URL生成器实例。
     *
     * @param string $collection 路由集合的名称
     * @return RouteCollectionUrlGenerator 返回对应的RouteCollectionUrlGenerator实例
     */
    public function to($collection)
    {
        return $this->routes[$collection];
    }
}
