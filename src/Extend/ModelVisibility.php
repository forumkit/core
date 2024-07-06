<?php

namespace Forumkit\Extend;

use Exception;
use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

/**
 * 模型可见性作用域允许我们根据当前用户限定查询范围。
 * 这主要用于仅显示用户有权查看的模型实例。
 *
 * 这是通过将查询通过一系列“作用域”回调函数来实现的，这些回调函数根据用户向查询添加额外的 `where`子句。
 *
 * 作用域被归类在一种能力下。
 * 在查询上调用 `whereVisibleTo` 会应用 `view` 能力下的作用域。通常，主要的 `view` 作用域可以请求其他能力的作用域，
 * 这为扩展提供了修改查询某些限制的入口点。
 *
 * 通过 `scopeAll` 注册的作用域将应用于模型下的所有查询，无论能力如何，并将接受能力名称作为额外的参数。
 */
class ModelVisibility implements ExtenderInterface
{
    private $modelClass;
    private $scopers = [];
    private $allScopers = [];

    /**
     * @param string $modelClass: 要应用作用域的模型的::class属性
     *                           该模型必须继承自 \Forumkit\Database\AbstractModel
     *                           并使用 \Forumkit\Database\ScopeVisibilityTrait
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;

        if (class_exists($this->modelClass) && ! is_callable([$modelClass, 'registerVisibilityScoper'])) {
            throw new Exception("模型 $modelClass 不能用于可见性作用域，因为它没有使用 Forumkit\Database\ScopeVisibilityTrait.");
        }
    }

    /**
     * 为给定能力添加作用域。
     *
     * @param callable|string $callback
     * @param string $ability: 默认为 'view'
     *
     * 回调函数可以是闭包或可调用类，并应接受：
     * - \Forumkit\User\User $actor
     * - \Illuminate\Database\Eloquent\Builder $query
     *
     * 回调函数应返回 void
     *
     * @return self
     */
    public function scope($callback, string $ability = 'view'): self
    {
        $this->scopers[$ability][] = $callback;

        return $this;
    }

    /**
     * 添加一个作用域，无论请求哪种能力，这个作用域都会为这个模型运行。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是闭包或可调用类，并应接受：
     * - \Forumkit\User\User $actor
     * - \Illuminate\Database\Eloquent\Builder $query
     * - string $ability
     *
     * 回调函数应返回 void
     *
     * @return self
     */
    public function scopeAll($callback): self
    {
        $this->allScopers[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! class_exists($this->modelClass)) {
            return;
        }

        foreach ($this->scopers as $ability => $scopers) {
            foreach ($scopers as $scoper) {
                $this->modelClass::registerVisibilityScoper(ContainerUtil::wrapCallback($scoper, $container), $ability);
            }
        }

        foreach ($this->allScopers as $scoper) {
            $this->modelClass::registerVisibilityScoper(ContainerUtil::wrapCallback($scoper, $container));
        }
    }
}
