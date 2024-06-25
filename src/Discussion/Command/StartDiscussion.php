<?php

namespace Forumkit\Discussion\Command;

use Forumkit\User\User;

class StartDiscussion
{
    /**
     * The user authoring the discussion.
     *
     * @var User
     */
    public $actor;

    /**
     * The discussion attributes.
     *
     * @var array
     */
    public $data;

    /**
     * The current ip address of the actor.
     *
     * @var string
     */
    public $ipAddress;

    /**
     * @param User   $actor The user authoring the discussion.
     * @param array  $data  The discussion attributes.
     * @param string $ipAddress The current ip address of the actor.
     */
    public function __construct(User $actor, array $data, string $ipAddress)
    {
        $this->actor = $actor;
        $this->data = $data;
        $this->ipAddress = $ipAddress;
    }
}
