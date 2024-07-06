<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;

class GroupsChanged
{
    /**
     * 用户的组别已更改的用户。
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
     * @param User $user 组别已更改的用户
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
