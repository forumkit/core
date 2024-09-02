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
     * Get a list of the filters that are active.
     *
     * @return FilterInterface[]
     */
    public function getActiveFilters()
    {
        return $this->activeFilters;
    }

    /**
     * Add a filter as being active.
     *
     * @param FilterInterface $filter
     * @return void
     */
    public function addActiveFilter(FilterInterface $filter)
    {
        $this->activeFilters[] = $filter;
    }
}
