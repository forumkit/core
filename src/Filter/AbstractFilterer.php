<?php

namespace Forumkit\Filter;

use Forumkit\Query\ApplyQueryParametersTrait;
use Forumkit\Query\QueryCriteria;
use Forumkit\Query\QueryResults;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use InvalidArgumentException;

abstract class AbstractFilterer
{
    use ApplyQueryParametersTrait;

    protected $filters;

    protected $filterMutators;

    /**
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(array $filters, array $filterMutators)
    {
        $this->filters = $filters;
        $this->filterMutators = $filterMutators;
    }

    abstract protected function getQuery(User $actor): Builder;

    /**
     * @param QueryCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     *
     * @return QueryResults
     * @throws InvalidArgumentException
     */
    public function filter(QueryCriteria $criteria, int $limit = null, int $offset = 0): QueryResults
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $filterState = new FilterState($query->getQuery(), $actor);

        foreach ($criteria->query as $filterKey => $filterValue) {
            $negate = false;
            if (substr($filterKey, 0, 1) == '-') {
                $negate = true;
                $filterKey = substr($filterKey, 1);
            }
            foreach (Arr::get($this->filters, $filterKey, []) as $filter) {
                $filterState->addActiveFilter($filter);
                $filter->filter($filterState, $filterValue, $negate);
            }
        }

        $this->applySort($filterState, $criteria->sort, $criteria->sortIsDefault);
        $this->applyOffset($filterState, $offset);
        $this->applyLimit($filterState, $limit + 1);

        foreach ($this->filterMutators as $mutator) {
            $mutator($filterState, $criteria);
        }

        // 执行过滤查询并获取结果。
        // 我们获取的结果数量比用户要求的多一个，这样我们就可以判断是否有更多的结果。
        // 如果有，我们会去掉那个多余的结果。
        $results = $query->get();

        if ($areMoreResults = $limit > 0 && $results->count() > $limit) {
            $results->pop();
        }

        return new QueryResults($results, $areMoreResults);
    }
}
