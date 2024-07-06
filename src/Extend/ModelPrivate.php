<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

/**
 * 某些模型，特别是 Discussion 和 CommentPost，旨在支持“私有”模式，
 * 在该模式下，除非满足某些条件，否则它们是不可见的。
 * 这可以用于实现从私有讨论到帖子审批等各种功能。
 *
 * 当一个模型被保存时，为该模型注册的所有“隐私检查器”都会被执行。
 * 如果任何隐私检查器返回 `true`, 则该模型实例的 `is_private` 字段将被设置为 `true`。 否则，它将被设置为 `false`。
 * 因此，这仅适用于具有 `is_private`字段的模型。
 *
 * 在 Forumkit 核心中，Discussion 和 CommentPost 模型具有私有支持。
 * 核心还包含可见性作用域，这些作用域会隐藏查询中具有 `is_private = true` 的这些模型实例。
 * 扩展可以通过使用 `viewPrivate` 能力为这些类注册自定义作用域，以在某些条件下授予查看某些私有实例的权限。
 */
class ModelPrivate implements ExtenderInterface
{
    private $modelClass;
    private $checkers = [];

    /**
     * @param string $modelClass: 要应用私有检查器的模型的::class属性
     *                           该模型必须具有 `is_private` 字段。
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * 添加模型隐私检查器。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受：
     * - \Forumkit\Database\AbstractModel $instance: 模型的实例。
     *
     * 如果模型实例应设为私有，则应返回 `true` 
     *
     * @return self
     */
    public function checker($callback): self
    {
        $this->checkers[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! class_exists($this->modelClass)) {
            return;
        }

        $container->extend('forumkit.database.model_private_checkers', function ($originalCheckers) use ($container) {
            foreach ($this->checkers as $checker) {
                $originalCheckers[$this->modelClass][] = ContainerUtil::wrapCallback($checker, $container);
            }

            return $originalCheckers;
        });
    }
}
