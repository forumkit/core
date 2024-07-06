<?php

namespace Forumkit\Filter;

use Forumkit\Query\AbstractQueryState;

class FilterState extends AbstractQueryState
{
    /**
     * @var FilterInterface[]
     */
    protected $activeFilters = [];

    /**
     * 获取当前活动的过滤器列表。
     *
     * @return FilterInterface[]
     */
    public function getActiveFilters()
    {
        return $this->activeFilters;
    }

    /**
     * 将一个过滤器标记为活动状态。
     *
     * @param FilterInterface $filter
     * @return void
     */
    public function addActiveFilter(FilterInterface $filter)
    {
        $this->activeFilters[] = $filter;
    }
}
