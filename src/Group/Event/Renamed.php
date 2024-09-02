<?php

namespace Forumkit\Group\Event;

use Forumkit\Group\Group;
use Forumkit\User\User;

class Renamed
{
    /**
     * @var \Forumkit\Group\Group
     */
    public $group;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Forumkit\Group\Group $group
     * @param User $actor
     */
    public function __construct(Group $group, User $actor = null)
    {
        $this->group = $group;
        $this->actor = $actor;
    }
}
