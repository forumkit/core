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
     * @param string $filtererClass: The ::class attribute of the filterer to extend.
     */
    public function __construct($filtererClass)
    {
        $this->filtererClass = $filtererClass;
    }

    /**
     * Add a filter to run when the filtererClass is filtered.
     *
     * @param string $filterClass: The ::class attribute of the filter you are adding.
     * @return self
     */
    public function addFilter(string $filterClass): self
    {
        $this->filters[] = $filterClass;

        return $this;
    }

    /**
     * Add a callback through which to run all filter queries after filters have been applied.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - Forumkit\Filter\FilterState $filter
     * - Forumkit\Query\QueryCriteria $criteria
     *
     * The callable should return void.
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
