<?php

namespace Forumkit\Post\Filter;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;

class TypeFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'type';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $type = $this->asString($filterValue);

        $filterState->getQuery()->where('posts.type', $negate ? '!=' : '=', $type);
    }
}
