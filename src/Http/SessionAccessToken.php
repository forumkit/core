<?php

namespace Forumkit\Http;

class SessionAccessToken extends AccessToken
{
    public static $type = 'session';

    protected static $lifetime = 60 * 60;  // 1 hour

    protected $hidden = ['token'];
}
