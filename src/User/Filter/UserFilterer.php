<?php

namespace Forumkit\User\Filter;

use Forumkit\Filter\AbstractFilterer;
use Forumkit\User\User;
use Forumkit\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class UserFilterer extends AbstractFilterer
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(UserRepository $users, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);

        $this->users = $users;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->users->query()->whereVisibleTo($actor);
    }
}
