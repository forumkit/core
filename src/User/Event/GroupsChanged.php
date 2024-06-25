<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class GroupsChanged
{
    /**
     * The user whose groups were changed.
     *
     * @var User
     */
    public $user;

    /**
     * @var \Forumkit\Group\Group[]
     */
    public $oldGroups;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param User $user The user whose groups were changed.
     * @param \Forumkit\Group\Group[] $oldGroups
     * @param User $actor
     */
    public function __construct(User $user, array $oldGroups, User $actor = null)
    {
        $this->user = $user;
        $this->oldGroups = $oldGroups;
        $this->actor = $actor;
    }
}
