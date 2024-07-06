<?php

namespace Forumkit\Http\Middleware;

use FastRoute\Dispatcher;
use Forumkit\Http\Exception\MethodNotAllowedException;
use Forumkit\Http\Exception\RouteNotFoundException;
use Forumkit\Http\RouteCollection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ResolveRoute implements Middleware
{
    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var Dispatcher|null
     */
    protected $dispatcher;

    /**
     * 创建中间件实例。
     *
     * @param RouteCollection $routes 路由集合对象
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * 从我们的路由集合中解析给定的请求。
     *
     * @return Response 返回响应对象
     *
     * @throws MethodNotAllowedException 抛出方法不允许异常
     * @throws RouteNotFoundException 抛出路由未找到异常
     */
    public function process(Request $request, Handler $handler): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath() ?: '/';

        $routeInfo = $this->getDispatcher()->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RouteNotFoundException($uri);
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException($method);
            default:
                $request = $request
                    ->withAttribute('routeName', $routeInfo[1]['name'])
                    ->withAttribute('routeHandler', $routeInfo[1]['handler'])
                    ->withAttribute('routeParameters', $routeInfo[2]);

                return $handler->handle($request);
        }
    }

    protected function getDispatcher()
    {
        if (! isset($this->dispatcher)) {
            $this->dispatcher = new Dispatcher\GroupCountBased($this->routes->getRouteData());
        }

        return $this->dispatcher;
    }
}
