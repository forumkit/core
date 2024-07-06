<?php

namespace Forumkit\Discussion\Command;

use Exception;
use Forumkit\Discussion\Discussion;
use Forumkit\Discussion\DiscussionValidator;
use Forumkit\Discussion\Event\Saving;
use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\Post\Command\PostReply;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Arr;

class StartDiscussionHandler
{
    use DispatchEventsTrait;

    /**
     * @var BusDispatcher
     */
    protected $bus;

    /**
     * @var \Forumkit\Discussion\DiscussionValidator
     */
    protected $validator;

    /**
     * @param EventDispatcher $events
     * @param BusDispatcher $bus
     * @param \Forumkit\Discussion\DiscussionValidator $validator
     */
    public function __construct(EventDispatcher $events, BusDispatcher $bus, DiscussionValidator $validator)
    {
        $this->events = $events;
        $this->bus = $bus;
        $this->validator = $validator;
    }

    /**
     * @param StartDiscussion $command
     * @return mixed
     * @throws Exception
     */
    public function handle(StartDiscussion $command)
    {
        $actor = $command->actor;
        $data = $command->data;
        $ipAddress = $command->ipAddress;

        $actor->assertCan('startDiscussion');

        // 创建一个新的讨论实体，保存它，并派发领域事件。
        // 然而，在保存之前，派发一个事件以让插件有机会根据控制器中可能传递的命令数据来修改讨论实体。
        $discussion = Discussion::start(
            Arr::get($data, 'attributes.title'),
            $actor
        );

        $this->events->dispatch(
            new Saving($discussion, $actor, $data)
        );

        $this->validator->assertValid($discussion->getAttributes());

        $discussion->save();

        // 现在讨论已经创建，我们可以添加第一个帖子。我们将通过运行 PostReply 命令来完成此操作。
        try {
            $post = $this->bus->dispatch(
                new PostReply($discussion->id, $actor, $data, $ipAddress, true)
            );
        } catch (Exception $e) {
            $discussion->delete();

            throw $e;
        }

        // 在我们派发事件之前，刷新讨论实例的属性，因为发布回复会改变其中的一些属性（例如最后时间）。
        $discussion->setRawAttributes($post->discussion->getAttributes(), true);
        $discussion->setFirstPost($post);
        $discussion->setLastPost($post);

        $this->dispatchEventsFor($discussion, $actor);

        $discussion->save();

        return $discussion;
    }
}
