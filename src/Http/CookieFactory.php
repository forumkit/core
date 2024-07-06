<?php

namespace Forumkit\Http;

use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Forumkit\Foundation\Config;

class CookieFactory
{
    /**
     * Cookie名称的前缀。
     *
     * @var string
     */
    protected $prefix;

    /**
     * Cookie的路径范围。
     *
     * @var string
     */
    protected $path;

    /**
     * Cookie的域名范围。
     *
     * @var string
     */
    protected $domain;

    /**
     * 是否只能通过HTTPS请求Cookie。
     *
     * @var bool
     */
    protected $secure;

    /**
     * SameSite Cookie的值。
     *
     * @var string|null
     */
    protected $samesite;

    /**
     * @param Config $config 配置对象
     */
    public function __construct(Config $config)
    {
        // 如果需要，我们将使用站点的基URL来确定Cookie设置的智能默认值
        $url = $config->url();

        // 从配置中获取Cookie设置或使用默认值
        $this->prefix = $config['cookie.name'] ?? 'forumkit';
        $this->path = $config['cookie.path'] ?? $url->getPath() ?: '/';
        $this->domain = $config['cookie.domain'];
        $this->secure = $config['cookie.secure'] ?? $url->getScheme() === 'https';
        $this->samesite = $config['cookie.samesite'];
    }

    /**
     * 创建一个新的Cookie实例。
     *
     * 此方法返回一个用于Set-Cookie HTTP头的Cookie实例。
     * 它将根据Forumkit的基URL和协议进行预配置。
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $maxAge
     * @return \Dflydev\FigCookies\SetCookie
     */
    public function make(string $name, string $value = null, int $maxAge = null): SetCookie
    {
        $cookie = SetCookie::create($this->getName($name), $value);

        // 确保我们同时发送MaxAge和Expires参数（因为不是所有浏览器版本都支持前者）
        if ($maxAge) {
            $cookie = $cookie
                ->withMaxAge($maxAge)
                ->withExpires(time() + $maxAge);
        }

        if ($this->domain != null) {
            $cookie = $cookie->withDomain($this->domain);
        }

        // 明确设置SameSite值，如果没有提供值则使用合理的默认值
        $cookie = $cookie->withSameSite(SameSite::{$this->samesite ?? 'lax'}());

        return $cookie
            ->withPath($this->path)
            ->withSecure($this->secure)
            ->withHttpOnly(true);
    }

    /**
     * 创建一个已过期的Cookie实例。
     *
     * @param string $name Cookie名称
     * @return \Dflydev\FigCookies\SetCookie 过期的Cookie实例
     */
    public function expire(string $name): SetCookie
    {
        return $this->make($name)->expire();
    }

    /**
     * 获取Cookie名称。
     *
     * @param string $name Cookie名称
     * @return string 带前缀的Cookie名称
     */
    public function getName(string $name): string
    {
        return $this->prefix.'_'.$name;
    }
}
