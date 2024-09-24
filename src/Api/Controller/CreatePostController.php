<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\PostSerializer;
use Forumkit\Discussion\Command\ReadDiscussion;
use Forumkit\Http\RequestUtil;
use Forumkit\Post\Command\PostReply;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreatePostController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = PostSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'discussion',
        'discussion.posts',
        'discussion.lastPostedUser'
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
        $data = Arr::get($request->getParsedBody(), 'data', []);
        $discussionId = Arr::get($data, 'relationships.discussion.data.id');
        $ipAddress = $request->getAttribute('ipAddress');

        $post = $this->bus->dispatch(
            new PostReply($discussionId, $actor, $data, $ipAddress)
        );

        // 回复后，我们假设用户已经查看了讨论中的所有帖子
        // 因此，如果用户已登录，我们将标记该讨论为已读
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $post->number)
            );
        }

        $discussion = $post->discussion;
        $discussion->posts = $discussion->posts()->whereVisibleTo($actor)->orderBy('created_at')->pluck('id');

        $this->loadRelations($post->newCollection([$post]), $this->extractInclude($request), $request);

        return $post;
    }
}
