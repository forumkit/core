<?php

namespace Forumkit\Foundation;

use Illuminate\Contracts\Container\Container;

class ContainerUtil
{
    /**
     * 封装一个回调函数，使得基于字符串的可调用类（invokable classes）在实际使用时才进行解析。
     *
     * @internal 不保证向后兼容性。
     *
     * @param callable|string $callback: 一个可调用的函数、全局函数或可调用类的 ::class 属性
     * @param Container $container 容器实例
     */
    public static function wrapCallback($callback, Container $container)
    {
        if (is_string($callback) && ! is_callable($callback)) {
            $callback = function (&...$args) use ($container, $callback) {
                $callback = $container->make($callback);

                return $callback(...$args);
            };
        }

        return $callback;
    }
}
