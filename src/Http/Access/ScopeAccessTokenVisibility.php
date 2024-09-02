<?php

namespace Forumkit\Http\Access;

use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeAccessTokenVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->isGuest()) {
            $query->whereRaw('FALSE');
        } elseif (! $actor->hasPermission('moderateAccessTokens')) {
            $query->where('user_id', $actor->id);
        }
    }
}
