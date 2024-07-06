<?php

namespace Forumkit\Discussion\Query;

use Forumkit\Filter\FilterInterface;
use Forumkit\Filter\FilterState;
use Forumkit\Filter\ValidateFilterTrait;
use Forumkit\Search\AbstractRegexGambit;
use Forumkit\Search\SearchState;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class CreatedFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'created:(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), Arr::get($matches, 1), Arr::get($matches, 3), $negate);
    }

    public function getFilterKey(): string
    {
        return 'created';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $filterValue = $this->asString($filterValue);

        preg_match('/^'.$this->getGambitPattern().'$/i', 'created:'.$filterValue, $matches);

        $this->constrain($filterState->getQuery(), Arr::get($matches, 1), Arr::get($matches, 3), $negate);
    }

    public function constrain(Builder $query, ?string $firstDate, ?string $secondDate, $negate)
    {
        // 如果只提供了一个 YYYY-MM-DD 日期，那么找到在那一天恰好开始的讨论。
        // 但是，如果提供了一个 YYYY-MM-DD..YYYY-MM-DD 范围，那么找到在那一时间段内开始的讨论。
        if (empty($secondDate)) {
            $query->whereDate('created_at', $negate ? '!=' : '=', $firstDate);
        } else {
            $query->whereBetween('created_at', [$firstDate, $secondDate], 'and', $negate);
        }
    }
}
