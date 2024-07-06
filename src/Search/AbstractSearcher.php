<?php

namespace Forumkit\Search;

use Forumkit\Query\ApplyQueryParametersTrait;
use Forumkit\Query\QueryCriteria;
use Forumkit\Query\QueryResults;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractSearcher
{
    use ApplyQueryParametersTrait;

    /**
     * @var GambitManager
     */
    protected $gambits;

    /**
     * @var array
     */
    protected $searchMutators;

    public function __construct(GambitManager $gambits, array $searchMutators)
    {
        $this->gambits = $gambits;
        $this->searchMutators = $searchMutators;
    }

    abstract protected function getQuery(User $actor): Builder;

    /**
     * @param QueryCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     *
     * @return QueryResults
     */
    public function search(QueryCriteria $criteria, $limit = null, $offset = 0): QueryResults
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $search = new SearchState($query->getQuery(), $actor);

        $this->gambits->apply($search, $criteria->query['q']);
        $this->applySort($search, $criteria->sort, $criteria->sortIsDefault);
        $this->applyOffset($search, $offset);
        $this->applyLimit($search, $limit + 1);

        foreach ($this->searchMutators as $mutator) {
            $mutator($search, $criteria);
        }

        //  执行搜索查询并获取结果。我们获取的结果数量比用户请求的多一个，以便我们可以判断是否有更多的结果。如果有，我们将去掉那个额外的结果。
        $results = $query->get();

        if ($areMoreResults = $limit > 0 && $results->count() > $limit) {
            $results->pop();
        }

        return new QueryResults($results, $areMoreResults);
    }
}
