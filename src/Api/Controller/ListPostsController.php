<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\PostSerializer;
use Forumkit\Http\RequestUtil;
use Forumkit\Http\UrlGenerator;
use Forumkit\Post\Filter\PostFilterer;
use Forumkit\Post\PostRepository;
use Forumkit\Query\QueryCriteria;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ListPostsController extends AbstractListController
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
     * {@inheritdoc}
     */
    public $sortFields = ['number', 'createdAt'];

    /**
     * @var PostFilterer
     */
    protected $filterer;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param PostFilterer $filterer
     * @param PostRepository $posts
     * @param UrlGenerator $url
     */
    public function __construct(PostFilterer $filterer, PostRepository $posts, UrlGenerator $url)
    {
        $this->filterer = $filterer;
        $this->posts = $posts;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $results = $this->filterer->filter(new QueryCriteria($actor, $filters, $sort, $sortIsDefault), $limit, $offset);

        $document->addPaginationLinks(
            $this->url->to('api')->route('posts.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        // 预先加载讨论关联数据，用于权限策略中，
        // 但预先加载不会影响 JSON 响应，
        // 响应中只包含请求中指定的关联数据。
        if (! in_array('discussion', $include)) {
            $include[] = 'discussion';
        }

        if (in_array('user', $include)) {
            $include[] = 'user.groups';
        }

        $results = $results->getResults();

        $this->loadRelations($results, $include, $request);

        return $results;
    }

    protected function extractSort(ServerRequestInterface $request)
    {
        $sort = [];

        foreach ((parent::extractSort($request) ?: []) as $field => $direction) {
            $sort["posts.$field"] = $direction;
        }

        return $sort;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractOffset(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $queryParams = $request->getQueryParams();
        $sort = $this->extractSort($request);
        $limit = $this->extractLimit($request);
        $filter = $this->extractFilter($request);

        if (($near = Arr::get($queryParams, 'page.near')) > 1) {
            if (count($filter) > 1 || ! isset($filter['discussion']) || $sort) {
                throw new InvalidParameterException(
                    'You can only use page[near] with filter[discussion] and the default sort order'
                );
            }

            $offset = $this->posts->getIndexForNumber((int) $filter['discussion'], $near, $actor);

            return max(0, $offset - $limit / 2);
        }

        return parent::extractOffset($request);
    }
}
