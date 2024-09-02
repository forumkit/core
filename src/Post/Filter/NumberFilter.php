<?php

namespace Forumkit\Post\Filter;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;

class NumberFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'number';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $number = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.number', $negate ? '!=' : '=', $number);
    }
}
