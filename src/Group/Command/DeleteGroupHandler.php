<?php

namespace Forumkit\Group\Command;

use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\Group\Event\Deleting;
use Forumkit\Group\GroupRepository;
use Forumkit\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteGroupHandler
{
    use DispatchEventsTrait;

    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param GroupRepository $groups
     */
    public function __construct(Dispatcher $events, GroupRepository $groups)
    {
        $this->groups = $groups;
        $this->events = $events;
    }

    /**
     * @param DeleteGroup $command
     * @return \Forumkit\Group\Group
     * @throws PermissionDeniedException
     */
    public function handle(DeleteGroup $command)
    {
        $actor = $command->actor;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $actor->assertCan('delete', $group);

        $this->events->dispatch(
            new Deleting($group, $actor, $command->data)
        );

        $group->delete();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
