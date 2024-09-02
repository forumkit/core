<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\Config;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Uri;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class Link implements ExtenderInterface
{
    protected $setRel = null;
    protected $setTarget = null;

    public function setRel(callable $callable)
    {
        $this->setRel = $callable;

        return $this;
    }

    public function setTarget(callable $callable)
    {
        $this->setTarget = $callable;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $siteUrl = $container->make(Config::class)->url();

        (new Formatter)->render(function (Renderer $renderer, $context, string $xml) use ($siteUrl) {
            return Utils::replaceAttributes($xml, 'URL', function ($attributes) use ($siteUrl) {
                $uri = isset($attributes['url'])
                    ? new Uri($attributes['url'])
                    : null;

                $setRel = $this->setRel;
                if ($setRel && $rel = $setRel($uri, $siteUrl, $attributes)) {
                    $attributes['rel'] = $rel;
                }

                $setTarget = $this->setTarget;
                if ($setTarget && $target = $setTarget($uri, $siteUrl, $attributes)) {
                    $attributes['target'] = $target;
                }

                return $attributes;
            });
        })->extend($container);
    }
}
