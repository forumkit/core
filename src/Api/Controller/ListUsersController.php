<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\UserSerializer;
use Forumkit\Http\RequestUtil;
use Forumkit\Http\UrlGenerator;
use Forumkit\Query\QueryCriteria;
use Forumkit\User\Filter\UserFilterer;
use Forumkit\User\Search\UserSearcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListUsersController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = UserSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * {@inheritdoc}
     */
    public $sortFields = [
        'username',
        'commentCount',
        'discussionCount',
        'lastSeenAt',
        'joinedAt'
    ];

    /**
     * @var UserFilterer
     */
    protected $filterer;

    /**
     * @var UserSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UserFilterer $filterer
     * @param UserSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(UserFilterer $filterer, UserSearcher $searcher, UrlGenerator $url)
    {
        $this->filterer = $filterer;
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertCan('searchUsers');

        if (! $actor->hasPermission('user.viewLastSeenAt')) {
            // 如果用户不能查看每个人的最后在线时间，我们阻止他们根据这个信息进行排序
            // 否则，这个排序字段会破坏披露在线状态的隐私设置
            // 我们使用 remove 而不是 add ，以便扩展程序仍然可以完全使用 extender 禁用此排序
            $this->removeSortField('lastSeenAt');
        }

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $criteria = new QueryCriteria($actor, $filters, $sort, $sortIsDefault);
        if (array_key_exists('q', $filters)) {
            $results = $this->searcher->search($criteria, $limit, $offset);
        } else {
            $results = $this->filterer->filter($criteria, $limit, $offset);
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('users.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        $results = $results->getResults();

        $this->loadRelations($results, $include, $request);

        return $results;
    }
}
