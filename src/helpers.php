<?php

use Forumkit\Foundation\Paths;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;

if (! function_exists('resolve')) {
    /**
     * 从容器中解析一个服务。
     *
     * @template T
     * @param string|class-string<T> $name 服务名称或类名
     * @param array $parameters 传递给服务的参数
     * @return T|mixed 解析后的服务实例或混合类型
     */
    function resolve(string $name, array $parameters = [])
    {
        return Container::getInstance()->make($name, $parameters);
    }
}

// 以下函数均已被永久弃用。
// 它们被我们所使用的一些 Laravel 组件（例如任务调度）所需要在扩展代码中不应使用它们。

if (! function_exists('app')) {
    /**
     * @deprecated 永久弃用
     *
     * @param  string  $make 要创建的服务名
     * @param  array   $parameters 传递给服务的参数
     * @return mixed|\Illuminate\Container\Container 返回解析后的服务实例或容器实例
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
     * @deprecated 永久弃用
     *
     * 获取安装基础目录的路径
     *
     * @param  string  $path 子路径（可选）
     * @return string 返回路径字符串
     */
    function base_path($path = '')
    {
        return resolve(Paths::class)->base.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * @deprecated 永久弃用
     *
     * 获取公共文件夹的路径
     *
     * @param  string  $path 子路径（可选）
     * @return string 返回路径字符串
     */
    function public_path($path = '')
    {
        return resolve(Paths::class)->public.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * @deprecated 永久弃用
     *
     * 获取公共文件夹的路径
     *
     * @param  string  $path 子路径（可选）
     * @return string 返回路径字符串
     */
    function storage_path($path = '')
    {
        return resolve(Paths::class)->storage.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('event')) {
    /**
     * @deprecated 永久弃用
     *
     * 触发一个事件并调用其监听器。
     *
     * @param  string|object  $event 事件名称或事件对象
     * @param  mixed  $payload 传递给监听器的数据
     * @param  bool  $halt 是否在第一个监听器返回false时停止事件传播
     * @return array|null 监听器返回值的数组或null
     */
    function event($event, $payload = [], $halt = false)
    {
        return resolve('events')->dispatch($event, $payload, $halt);
    }
}

if (! function_exists('config')) {
    /**
     * @deprecated 不要使用，将转移到forumkit/laravel-helpers包
     */
    function config(string $key, $default = null)
    {
        return resolve(Repository::class)->get($key, $default);
    }
}
