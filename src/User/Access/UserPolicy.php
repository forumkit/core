<?php

namespace Forumkit\User\Access;

use Forumkit\User\User;

class UserPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function can(User $actor, $ability)
    {
        if ($actor->hasPermission('user.'.$ability)) {
            return $this->allow();
        }
    }

    /**
     * @param User $actor
     * @param User $user
     */
    public function editCredentials(User $actor, User $user)
    {
        if ($user->isAdmin() && ! $actor->isAdmin()) {
            return $this->deny();
        }

        if ($actor->hasPermission('user.editCredentials')) {
            return $this->allow();
        }
    }

    public function uploadAvatar(User $actor, User $user)
    {
        if ($actor->id === $user->id) {
            return $this->allow();
        }

        if ($actor->id !== $user->id) {
            return $actor->can('edit', $user);
        }
    }
}
