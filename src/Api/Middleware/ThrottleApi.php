<?php

namespace Forumkit\Api\Middleware;

use Forumkit\Post\Exception\FloodingException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ThrottleApi implements Middleware
{
    protected $throttlers;

    public function __construct(array $throttlers)
    {
        $this->throttlers = $throttlers;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($this->throttle($request)) {
            throw new FloodingException;
        }

        return $handler->handle($request);
    }

    /**
     * @return bool
     */
    public function throttle(Request $request): bool
    {
        $throttle = false;
        foreach ($this->throttlers as $throttler) {
            $result = $throttler($request);

            // 显式返回 false 将覆盖所有节流限制，即请求不会被限制
            if ($result === false) {
                return false;
            } elseif ($result === true) {
                $throttle = true;
            }
        }

        return $throttle;
    }
}
