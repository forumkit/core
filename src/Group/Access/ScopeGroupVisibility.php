<?php

namespace Forumkit\Group\Access;

use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeGroupVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->cannot('viewHiddenGroups')) {
            $query->where('is_hidden', false);
        }
    }
}
