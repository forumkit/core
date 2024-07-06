<?php

namespace Forumkit\Discussion;

use Forumkit\Post\Event\Deleted;
use Illuminate\Contracts\Events\Dispatcher;

class UserStateUpdater
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Deleted::class, [$this, 'whenPostWasDeleted']);
    }

    /**
     * 更新用户在某个讨论中的状态。
     * 如果用户A读取讨论直至第N个帖子，而最近删除了X个帖子，
     * 那么我们需要将用户A的已读状态更新为新的N-X帖子编号，以便他们能被新的帖子通知。
     */
    public function whenPostWasDeleted(Deleted $event)
    {
        /*
         * 我们检查是否大于当前值，因为在这一点上DiscussionMetadataUpdater应该已经更新了最后一个帖子。
         */
        if ($event->post->number > $event->post->discussion->last_post_number) {
            UserState::query()
                ->where('discussion_id', $event->post->discussion_id)
                ->where('last_read_post_number', '>', $event->post->discussion->last_post_number)
                ->update(['last_read_post_number' => $event->post->discussion->last_post_number]);
        }
    }
}
