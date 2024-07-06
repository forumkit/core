<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Middleware implements ExtenderInterface
{
    private $addMiddlewares = [];
    private $removeMiddlewares = [];
    private $replaceMiddlewares = [];
    private $insertBeforeMiddlewares = [];
    private $insertAfterMiddlewares = [];
    private $frontend;

    /**
     * @param string $frontend: 前端的名称
     */
    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * 向前端添加新的中间件。
     *
     * @param string $middleware: 中间件类的 ::class 属性
     *                            必须实现 \Psr\Http\Server\MiddlewareInterface 接口
     * @return self
     */
    public function add(string $middleware): self
    {
        $this->addMiddlewares[] = $middleware;

        return $this;
    }

    /**
     * 替换前端已存在的中间件。
     *
     * @param string $originalMiddleware: 原中间件类的 ::class 属性 或容器绑定名称
     * @param string $newMiddleware: 中间件类的 ::class 属性
     *                            必须实现 \Psr\Http\Server\MiddlewareInterface 接口
     * @return self
     */
    public function replace(string $originalMiddleware, string $newMiddleware): self
    {
        $this->replaceMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    /**
     * 从前端移除中间件。
     *
     * @param string $middleware: 中间件类的 ::class 属性
     * @return self
     */
    public function remove(string $middleware): self
    {
        $this->removeMiddlewares[] = $middleware;

        return $this;
    }

    /**
     * 在已存在的中间件之前插入新的中间件。
     *
     * @param string $originalMiddleware: 原中间件类的 ::class 属性 或容器绑定名称
     * @param string $newMiddleware: 中间件类的 ::class 属性
     *                            必须实现 \Psr\Http\Server\MiddlewareInterface 接口
     * @return self
     */
    public function insertBefore(string $originalMiddleware, string $newMiddleware): self
    {
        $this->insertBeforeMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    /**
     * 在已存在的中间件之后插入新的中间件。
     *
     * @param string $originalMiddleware: 原中间件类的 ::class 属性 或容器绑定名称
     * @param string $newMiddleware: 中间件类的 ::class 属性
     *                            必须实现 \Psr\Http\Server\MiddlewareInterface 接口
     * @return self
     */
    public function insertAfter(string $originalMiddleware, string $newMiddleware): self
    {
        $this->insertAfterMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend("forumkit.{$this->frontend}.middleware", function ($existingMiddleware) {
            foreach ($this->addMiddlewares as $addMiddleware) {
                $existingMiddleware[] = $addMiddleware;
            }

            foreach ($this->replaceMiddlewares as $originalMiddleware => $newMiddleware) {
                $existingMiddleware = array_replace(
                    $existingMiddleware,
                    array_fill_keys(
                        array_keys($existingMiddleware, $originalMiddleware),
                        $newMiddleware
                    )
                );
            }

            foreach ($this->insertBeforeMiddlewares as $originalMiddleware => $newMiddleware) {
                array_splice(
                    $existingMiddleware,
                    array_search($originalMiddleware, $existingMiddleware),
                    0,
                    $newMiddleware
                );
            }

            foreach ($this->insertAfterMiddlewares as $originalMiddleware => $newMiddleware) {
                array_splice(
                    $existingMiddleware,
                    array_search($originalMiddleware, $existingMiddleware) + 1,
                    0,
                    $newMiddleware
                );
            }

            $existingMiddleware = array_diff($existingMiddleware, $this->removeMiddlewares);

            return $existingMiddleware;
        });
    }
}
