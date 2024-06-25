<?php

namespace Forumkit\Post\Filter;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;
use Forumkit\User\UserRepository;

class AuthorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * @var \Forumkit\User\UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $usernames = $this->asStringArray($filterValue);

        $ids = $this->users->query()->whereIn('username', $usernames)->pluck('id');

        $filterState->getQuery()->whereIn('posts.user_id', $ids, 'and', $negate);
    }
}
