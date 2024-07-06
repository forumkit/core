<?php

namespace Forumkit\Http\Middleware;

use Forumkit\Foundation\Config;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ReferrerPolicyHeader implements Middleware
{
    protected $policy = '';

    public function __construct(Config $config)
    {
        $this->policy = Arr::get($config, 'headers.referrerPolicy') ?? 'same-origin';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withAddedHeader('Referrer-Policy', $this->policy);
    }
}
