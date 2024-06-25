<?php

namespace Forumkit\Http\Filter;

use Forumkit\Filter\AbstractFilterer;
use Forumkit\Http\AccessToken;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenFilterer extends AbstractFilterer
{
    protected function getQuery(User $actor): Builder
    {
        return AccessToken::query()->whereVisibleTo($actor);
    }
}
