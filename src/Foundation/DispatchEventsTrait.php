<?php

namespace Forumkit\Foundation;

use Forumkit\User\User;
use Illuminate\Contracts\Events\Dispatcher;

trait DispatchEventsTrait
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * 调度实体的所有事件。
     *
     * @param object $entity
     * @param User $actor
     */
    public function dispatchEventsFor($entity, User $actor = null)
    {
        foreach ($entity->releaseEvents() as $event) {
            $event->actor = $actor;

            $this->events->dispatch($event);
        }
    }
}
