<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\PostSerializer;
use Forumkit\Http\RequestUtil;
use Forumkit\Post\PostRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowPostController extends AbstractShowController
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
        'user.groups',
        'editedUser',
        'hiddenUser',
        'discussion'
    ];

    /**
     * @var \Forumkit\Post\PostRepository
     */
    protected $posts;

    /**
     * @param \Forumkit\Post\PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $post = $this->posts->findOrFail(Arr::get($request->getQueryParams(), 'id'), RequestUtil::getActor($request));

        $include = $this->extractInclude($request);

        $this->loadRelations(new Collection([$post]), $include, $request);

        return $post;
    }
}
