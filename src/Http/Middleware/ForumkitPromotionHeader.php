<?php

namespace Forumkit\Http\Middleware;

use Forumkit\Foundation\Config;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ForumkitPromotionHeader implements Middleware
{
    protected $enabled = true;

    public function __construct(Config $config)
    {
        $this->enabled = Arr::get($config, 'headers.poweredByHeader') ?? true;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->enabled) {
            $response = $response->withAddedHeader('X-Powered-By', 'Forumkit');
        }

        return $response;
    }
}
