<?php

namespace Forumkit\Post\Filter;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;

class IdFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'id';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $ids = $this->asIntArray($filterValue);

        $filterState->getQuery()->whereIn('posts.id', $ids, 'and', $negate);
    }
}
