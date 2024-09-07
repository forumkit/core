<?php

use Forumkit\Foundation\Paths;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;

if (! function_exists('resolve')) {
    /**
     * 从容器中解析服务。
     *
     * @template T
     * @param string|class-string<T> $name
     * @param array $parameters
     * @return T|mixed
     */
    function resolve(string $name, array $parameters = [])
    {
        return Container::getInstance()->make($name, $parameters);
    }
}

// 以下所有内容都已永久弃用。
// 我们使用的一些laravel组件（例如任务调度）需要它们。
// 扩展代码中不应使用它们。

if (! function_exists('app')) {
    /**
     * @deprecated perpetually.
     *
     * @param  string  $make
     * @param  array   $parameters
     * @return mixed|\Illuminate\Container\Container
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        return resolve($make, $parameters);
    }
}

if (! function_exists('base_path')) {
    /**
     * @deprecated perpetually.
     *
     * 获取安装基础路径。
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        return resolve(Paths::class)->base.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * @deprecated perpetually.
     *
     * 获取public文件夹的路径。
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        return resolve(Paths::class)->public.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * @deprecated perpetually.
     *
     * 获取storage文件夹的路径。
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        return resolve(Paths::class)->storage.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('event')) {
    /**
     * @deprecated perpetually.
     *
     * 触发事件并调用监听器。
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function event($event, $payload = [], $halt = false)
    {
        return resolve('events')->dispatch($event, $payload, $halt);
    }
}

if (! function_exists('config')) {
    /**
     * @deprecated 请勿使用，将转移到forumkit/laravel-helpers。
     */
    function config(string $key, $default = null)
    {
        return resolve(Repository::class)->get($key, $default);
    }
}
