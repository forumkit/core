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

        // 创建讨论后，我们假设用户已看到讨论中的所有帖子;因此，如果他们已登录，我们会将讨论标记为已读。
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussion->id, $actor, 1)
            );
        }

        $this->loadRelations(new Collection([$discussion]), $this->extractInclude($request), $request);

        return $discussion;
    }
}
