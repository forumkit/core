<?php

namespace Forumkit\Group\Command;

use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\Group\Event\Saving;
use Forumkit\Group\Group;
use Forumkit\Group\GroupValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class CreateGroupHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Forumkit\Group\GroupValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param \Forumkit\Group\GroupValidator $validator
     */
    public function __construct(Dispatcher $events, GroupValidator $validator)
    {
        $this->events = $events;
        $this->validator = $validator;
    }

    /**
     * @param CreateGroup $command
     * @return \Forumkit\Group\Group
     * @throws \Forumkit\User\Exception\PermissionDeniedException
     */
    public function handle(CreateGroup $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $actor->assertRegistered();
        $actor->assertCan('createGroup');

        $group = Group::build(
            Arr::get($data, 'attributes.nameSingular'),
            Arr::get($data, 'attributes.namePlural'),
            Arr::get($data, 'attributes.color'),
            Arr::get($data, 'attributes.icon'),
            Arr::get($data, 'attributes.isHidden', false)
        );

        $this->events->dispatch(
            new Saving($group, $actor, $data)
        );

        $this->validator->assertValid($group->getAttributes());

        $group->save();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
