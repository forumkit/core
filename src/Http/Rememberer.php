<?php

namespace Forumkit\Http;

use Dflydev\FigCookies\FigResponseCookies;
use Psr\Http\Message\ResponseInterface;

class Rememberer
{
    const COOKIE_NAME = 'remember';

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

    /**
     * 在响应中设置记住cookie。
     * 
     * @param ResponseInterface $response 响应对象
     * @param RememberAccessToken $token 要在响应中设置的记住令牌
     * @return ResponseInterface 返回响应对象
     */
    public function remember(ResponseInterface $response, RememberAccessToken $token)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make(self::COOKIE_NAME, $token->token, RememberAccessToken::rememberCookieLifeTime())
        );
    }

    public function forget(ResponseInterface $response)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->expire(self::COOKIE_NAME)
        );
    }
}
