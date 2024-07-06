<?php

namespace Forumkit\Filter;

interface FilterInterface
{
    /**
     * 当查询包含具有此键的过滤参数时，才会运行此过滤器。
     */
    public function getFilterKey(): string;

    /**
     * 对查询进行过滤。
     *
     * @todo: 在 2.0 版本中，将 $filterValue 的类型更改为 mixed，因为它可能是一个数组。
     */
    public function filter(FilterState $filterState, string $filterValue, bool $negate);
}
