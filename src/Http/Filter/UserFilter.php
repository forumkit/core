<?php

namespace Forumkit\Http\Filter;

use Forumkit\Api\Controller\ListAccessTokensController;
use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;

/**
 * Filters an access tokens request by the related user.
 *
 * @see ListAccessTokensController
 */
class UserFilter implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * @inheritDoc
     */
    public function getFilterKey(): string
    {
        return 'user';
    }

    /**
     * @inheritDoc
     */
    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $filterValue = $this->asInt($filterValue);

        $filterState->getQuery()->where('user_id', $negate ? '!=' : '=', $filterValue);
    }
}
