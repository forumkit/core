<?php

namespace Forumkit\Http\Filter;

use Forumkit\Api\Controller\ListAccessTokensController;
use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;

/**
 * 根据相关用户过滤访问令牌请求。
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
