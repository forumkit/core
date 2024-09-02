<?php

namespace Forumkit\Http\Middleware;

use Forumkit\Http\AccessToken;
use Forumkit\Http\CookieFactory;
use Forumkit\Http\RememberAccessToken;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class RememberFromCookie implements Middleware
{
    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * @param CookieFactory $cookie
     */
    public function __construct(CookieFactory $cookie)
    {
        $this->cookie = $cookie;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $id = Arr::get($request->getCookieParams(), $this->cookie->getName('remember'));

        if ($id) {
            $token = AccessToken::findValid($id);

            if ($token && $token instanceof RememberAccessToken) {
                $token->touch($request);

                /** @var \Illuminate\Contracts\Session\Session $session */
                $session = $request->getAttribute('session');
                $session->put('access_token', $token->token);
            }
        }

        return $handler->handle($request);
    }
}
