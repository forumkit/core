<?php

namespace Forumkit\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ExecuteRoute implements Middleware
{
    /**
     * 执行 ResolveRoute 中解析的路由处理程序。
     */
    public function process(Request $request, Handler $handler): Response
    {
        $handler = $request->getAttribute('routeHandler');
        $parameters = $request->getAttribute('routeParameters');

        return $handler($request, $parameters);
    }
}
