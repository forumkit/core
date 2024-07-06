<?php

namespace Forumkit\Post\Command;

use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\Post\Event\Deleting;
use Forumkit\Post\PostRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeletePostHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Forumkit\Post\PostRepository
     */
    protected $posts;

    /**
     * @param Dispatcher $events
     * @param \Forumkit\Post\PostRepository $posts
     */
    public function __construct(Dispatcher $events, PostRepository $posts)
    {
        $this->events = $events;
        $this->posts = $posts;
    }

    /**
     * @param DeletePost $command
     * @return \Forumkit\Post\Post
     * @throws \Forumkit\User\Exception\PermissionDeniedException
     */
    public function handle(DeletePost $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $actor->assertCan('delete', $post);

        $this->events->dispatch(
            new Deleting($post, $actor, $command->data)
        );

        $post->delete();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
