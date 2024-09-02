<?php

namespace Forumkit\Admin\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class DisableBrowserCache implements Middleware
{
    public function process(Request $request, Handler $handler): Response
    {
        $response = $handler->handle($request);

        return $response->withHeader('Cache-Control', 'max-age=0, no-store');
    }
}
