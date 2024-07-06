<?php

namespace Forumkit\Http;

class SessionAccessToken extends AccessToken
{
    public static $type = 'session';

    protected static $lifetime = 60 * 60;  // 1小时

    protected $hidden = ['token'];
}
