<?php

namespace Forumkit\Group\Filter;

use Forumkit\Filter\AbstractFilterer;
use Forumkit\Group\GroupRepository;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class GroupFilterer extends AbstractFilterer
{
    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param GroupRepository $groups
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(GroupRepository $groups, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);

        $this->groups = $groups;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->groups->query()->whereVisibleTo($actor);
    }
}
