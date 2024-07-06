<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Filter implements ExtenderInterface
{
    private $filtererClass;
    private $filters = [];
    private $filterMutators = [];

    /**
     * @param string $filtererClass: 要扩展的过滤器的类名（使用::class属性）。
     */
    public function __construct($filtererClass)
    {
        $this->filtererClass = $filtererClass;
    }

    /**
     * 在filtererClass被过滤时添加一个过滤器。
     *
     * @param string $filterClass: 你要添加的过滤器的类名（使用::class属性）。
     * @return self
     */
    public function addFilter(string $filterClass): self
    {
        $this->filters[] = $filterClass;

        return $this;
    }

    /**
     * 添加一个回调函数，用于在所有过滤器应用后运行所有过滤查询。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - Forumkit\Filter\FilterState $filter 过滤器状态对象
     * - Forumkit\Query\QueryCriteria $criteria 查询条件对象
     *
     * 回调函数应返回void
     *
     * @return self
     */
    public function addFilterMutator($callback): self
    {
        $this->filterMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.filter.filters', function ($originalFilters) {
            foreach ($this->filters as $filter) {
                $originalFilters[$this->filtererClass][] = $filter;
            }

            return $originalFilters;
        });
        $container->extend('forumkit.filter.filter_mutators', function ($originalMutators) {
            foreach ($this->filterMutators as $mutator) {
                $originalMutators[$this->filtererClass][] = $mutator;
            }

            return $originalMutators;
        });
    }
}
