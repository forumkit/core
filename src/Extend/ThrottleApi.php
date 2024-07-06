<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class ThrottleApi implements ExtenderInterface
{
    private $setThrottlers = [];
    private $removeThrottlers = [];

    /**
     * 添加一个新的节流器（或用具有相同名称的节流器覆盖）。
     *
     * @param string $name: 节流器的名称
     * @param string|callable $callback
     *
     * 回调函数可以是一个闭包或可调用的类，并应该接受：
     *   - $request: 当前 `\Psr\Http\Message\ServerRequestInterface` 请求对象。
     *               可以使用 `\Forumkit\Http\RequestUtil::getActor($request)` 来获取当前用户。
     *               可以使用 `$request->getAttribute('routeName')` 来获取当前路由。
     * 请注意，默认情况下，每个节流器都会在每个路由上运行。
     * 如果您只想对特定路由进行节流，您需要在逻辑内部进行检查。
     *
     * 回调函数应该返回以下之一：
     *   - `false`: 这会将请求标记为不进行节流。它会覆盖所有其他节流器
     *   - `true`: 这会将请求标记为进行节流。
     *  其他所有输出都将被忽略。
     *
     * @return self
     */
    public function set(string $name, $callback): self
    {
        $this->setThrottlers[$name] = $callback;

        return $this;
    }

    /**
     * 移除已注册此名称的节流器。
     *
     * @param string $name: 要移除的节流器的名称
     * @return self
     */
    public function remove(string $name): self
    {
        $this->removeThrottlers[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.api.throttlers', function ($throttlers) use ($container) {
            $throttlers = array_diff_key($throttlers, array_flip($this->removeThrottlers));

            foreach ($this->setThrottlers as $name => $throttler) {
                $throttlers[$name] = ContainerUtil::wrapCallback($throttler, $container);
            }

            return $throttlers;
        });
    }
}
