<?php

namespace Forumkit\User\Access;

use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeUserVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->cannot('viewForum')) {
            if ($actor->isGuest()) {
                $query->whereRaw('FALSE');
            } else {
                $query->where('id', $actor->id);
            }
        }
    }
}
