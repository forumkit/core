<?php

namespace Forumkit\Discussion\Command;

use Forumkit\Discussion\DiscussionRepository;
use Forumkit\Discussion\Event\UserDataSaving;
use Forumkit\Foundation\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class ReadDiscussionHandler
{
    use DispatchEventsTrait;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @param Dispatcher $events
     * @param DiscussionRepository $discussions
     */
    public function __construct(Dispatcher $events, DiscussionRepository $discussions)
    {
        $this->events = $events;
        $this->discussions = $discussions;
    }

    /**
     * @param ReadDiscussion $command
     * @return \Forumkit\Discussion\UserState
     * @throws \Forumkit\User\Exception\PermissionDeniedException
     */
    public function handle(ReadDiscussion $command)
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $state = $discussion->stateFor($actor);
        $state->read($command->lastReadPostNumber);

        $this->events->dispatch(
            new UserDataSaving($state)
        );

        $state->save();

        $this->dispatchEventsFor($state);

        return $state;
    }
}
