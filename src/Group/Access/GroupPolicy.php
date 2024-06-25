<?php

namespace Forumkit\Group\Access;

use Forumkit\User\Access\AbstractPolicy;
use Forumkit\User\User;

class GroupPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function can(User $actor, $ability)
    {
        if ($actor->hasPermission('group.'.$ability)) {
            return $this->allow();
        }
    }
}
