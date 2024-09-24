<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\DiscussionSerializer;
use Forumkit\Discussion\Command\ReadDiscussion;
use Forumkit\Discussion\Command\StartDiscussion;
use Forumkit\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateDiscussionController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = DiscussionSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'posts',
        'user',
        'lastPostedUser',
        'firstPost',
        'lastPost'
    ];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $ipAddress = $request->getAttribute('ipAddress');

        $discussion = $this->bus->dispatch(
            new StartDiscussion($actor, Arr::get($request->getParsedBody(), 'data', []), $ipAddress)
        );

        // 创建讨论后，我们假设用户已经查看了讨论中的所有帖子
        // 因此，如果用户已登录（即 $actor->exists 为 true ），我们将标记该讨论为已读
        // 这里假设讨论中的第一个帖子编号为1（这可能根据具体实现有所不同），并将其作为参数传递给 ReadDiscussion 命令
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussion->id, $actor, 1)
            );
        }

        $this->loadRelations(new Collection([$discussion]), $this->extractInclude($request), $request);

        return $discussion;
    }
}
