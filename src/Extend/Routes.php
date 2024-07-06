<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Routes implements ExtenderInterface
{
    private $appName;

    private $routes = [];
    private $removedRoutes = [];

    /**
     * @param string $appName: 应用名称 (api, site, admin).
     */
    public function __construct(string $appName)
    {
        $this->appName = $appName;
    }

    /**
     * 添加一个GET路由。
     *
     * @param string $path: 路由的路径
     * @param string $name: 路由的名称，必须唯一
     * @param callable|string $handler: 控制器类的 ::class 属性，或者一个闭包
     *
     * 如果处理器是一个控制器类，它应该实现 \Psr\Http\Server\RequestHandlerInterface 接口
     * 或者继承 \Forumkit\Api\Controller 中的Forumkit Api控制器之一
     *
     * 处理器应该接受：
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: 如果它继承了Forumkit Api控制器之一
     *
     * 处理器应该返回
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function get(string $path, string $name, $handler): self
    {
        return $this->route('GET', $path, $name, $handler);
    }

    /**
     * 添加一个POST路由。
     *
     * @param string $path: 路由的路径
     * @param string $name: 路由的名称，必须唯一
     * @param callable|string $handler: 控制器类的 ::class 属性，或者一个闭包
     *
     * 如果处理器是一个控制器类，它应该实现 \Psr\Http\Server\RequestHandlerInterface 接口
     * 或者继承 \Forumkit\Api\Controller 中的Forumkit Api控制器之一
     *
     * 处理器应该接受：
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: 如果它继承了Forumkit Api控制器之一
     *
     * 处理器应该返回：
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function post(string $path, string $name, $handler): self
    {
        return $this->route('POST', $path, $name, $handler);
    }

    /**
     * 添加一个PUT路由。
     *
     * @param string $path: 路由的路径
     * @param string $name: 路由的名称，必须唯一
     * @param callable|string $handler: 控制器类的 ::class 属性，或者一个闭包
     *
     * 如果处理器是一个控制器类，它应该实现 \Psr\Http\Server\RequestHandlerInterface 接口
     * 或者继承 \Forumkit\Api\Controller 中的Forumkit Api控制器之一
     *
     * 处理器应该接受：
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: 如果它继承了Forumkit Api控制器之一
     *
     * 处理器应该返回：
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function put(string $path, string $name, $handler): self
    {
        return $this->route('PUT', $path, $name, $handler);
    }

    /**
     * 添加一个PATCH路由。
     *
     * @param string $path: 路由的路径
     * @param string $name: 路由的名称，必须唯一
     * @param callable|string $handler: 控制器类的 ::class 属性，或者一个闭包
     *
     * 如果处理器是一个控制器类，它应该实现 \Psr\Http\Server\RequestHandlerInterface 接口
     * 或者继承 \Forumkit\Api\Controller 中的Forumkit Api控制器之一
     *
     * 处理器应该接受：
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: 如果它继承了Forumkit Api控制器之一
     *
     * 处理器应该返回：
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function patch(string $path, string $name, $handler): self
    {
        return $this->route('PATCH', $path, $name, $handler);
    }

    /**
     * 添加一个DELETE路由。
     *
     * @param string $path: 路由的路径
     * @param string $name: 路由的名称，必须唯一
     * @param callable|string $handler: 控制器类的 ::class 属性，或者一个闭包
     *
     * 如果处理器是一个控制器类，它应该实现 \Psr\Http\Server\RequestHandlerInterface 接口
     * 或者继承 \Forumkit\Api\Controller 中的Forumkit Api控制器之一
     *
     * 处理器应该接受：
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: 如果它继承了Forumkit Api控制器之一
     *
     * 处理器应该返回：
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function delete(string $path, string $name, $handler): self
    {
        return $this->route('DELETE', $path, $name, $handler);
    }

    private function route(string $httpMethod, string $path, string $name, $handler): self
    {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'name' => $name,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * 移除已存在的路由。
     * 在覆盖路由前需要此操作。
     *
     * @param string $name: 路由的名称
     * @return self
     */
    public function remove(string $name): self
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "forumkit.{$this->appName}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->addRoute(
                        $route['method'],
                        $route['path'],
                        $route['name'],
                        $factory->toController($route['handler'])
                    );
                }
            }
        );
    }
}
