<?php

namespace Forumkit\Group\Filter;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;

class HiddenFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $hidden = $this->asBool($filterValue);

        $filterState->getQuery()->where('is_hidden', $negate ? '!=' : '=', $hidden);
    }
}
