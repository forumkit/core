<?php

namespace Forumkit\Post\Command;

use Carbon\Carbon;
use Forumkit\Discussion\DiscussionRepository;
use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\Notification\NotificationSyncer;
use Forumkit\Post\CommentPost;
use Forumkit\Post\Event\Saving;
use Forumkit\Post\PostValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class PostReplyHandler
{
    use DispatchEventsTrait;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var \Forumkit\Notification\NotificationSyncer
     */
    protected $notifications;

    /**
     * @var \Forumkit\Post\PostValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param DiscussionRepository $discussions
     * @param \Forumkit\Notification\NotificationSyncer $notifications
     * @param PostValidator $validator
     */
    public function __construct(
        Dispatcher $events,
        DiscussionRepository $discussions,
        NotificationSyncer $notifications,
        PostValidator $validator
    ) {
        $this->events = $events;
        $this->discussions = $discussions;
        $this->notifications = $notifications;
        $this->validator = $validator;
    }

    /**
     * @param PostReply $command
     * @return CommentPost
     * @throws \Forumkit\User\Exception\PermissionDeniedException
     */
    public function handle(PostReply $command)
    {
        $actor = $command->actor;

        // 确保用户有权限回复这个讨论。首先，
        // 确保讨论存在且用户有权限查看它；如果没有，
        // 抛出 ModelNotFound 异常，以免泄露讨论的存在。
        // 如果用户有权查看，检查他们是否有权限回复。
        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        // 如果这是讨论中的第一个帖子，从技术上来说它不是一个
        // “回复”，所以我们不会检查这个权限。
        if (! $command->isFirstPost) {
            $actor->assertCan('reply', $discussion);
        }

        // 创建一个新的 Post 实体，持久化它，并分发领域事件。
        // 在持久化之前，触发一个事件，给插件一个机会
        // 根据命令中的数据修改帖子实体。
        $post = CommentPost::reply(
            $discussion->id,
            Arr::get($command->data, 'attributes.content'),
            $actor->id,
            $command->ipAddress,
            $command->actor,
        );

        if ($actor->isAdmin() && ($time = Arr::get($command->data, 'attributes.createdAt'))) {
            $post->created_at = new Carbon($time);
        }

        $this->events->dispatch(
            new Saving($post, $actor, $command->data)
        );

        $this->validator->assertValid($post->getAttributes());

        $post->save();

        $this->notifications->onePerUser(function () use ($post, $actor) {
            $this->dispatchEventsFor($post, $actor);
        });

        return $post;
    }
}
