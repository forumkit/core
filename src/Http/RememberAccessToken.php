<?php

namespace Forumkit\Http;

class RememberAccessToken extends AccessToken
{
    public static $type = 'session_remember';

    protected static $lifetime = 5 * 365 * 24 * 60 * 60; // 5 年

    protected $hidden = ['token'];

    /**
     * 这是一个辅助方法，用于返回受保护的 $lifetime 属性的值
     * @return int
     */
    public static function rememberCookieLifeTime(): int
    {
        return self::$lifetime;
    }
}
