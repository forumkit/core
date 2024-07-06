<?php

namespace Forumkit\User\Query;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;
use Forumkit\Group\Group;
use Forumkit\Search\AbstractRegexGambit;
use Forumkit\Search\SearchState;
use Forumkit\User\User;
use Illuminate\Database\Query\Builder;

class GroupFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'group:(.+)';
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $search->getActor(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'group';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $this->constrain($filterState->getQuery(), $filterState->getActor(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, User $actor, $rawQuery, bool $negate)
    {
        $groupIdentifiers = $this->asStringArray($rawQuery);
        $groupQuery = Group::whereVisibleTo($actor);

        $ids = [];
        $names = [];
        foreach ($groupIdentifiers as $identifier) {
            if (is_numeric($identifier)) {
                $ids[] = $identifier;
            } else {
                $names[] = $identifier;
            }
        }

        $groupQuery->whereIn('groups.id', $ids)
            ->orWhereIn('name_singular', $names)
            ->orWhereIn('name_plural', $names);

        $userIds = $groupQuery->join('group_user', 'groups.id', 'group_user.group_id')
            ->pluck('group_user.user_id')
            ->all();

        $query->whereIn('id', $userIds, 'and', $negate);
    }
}
