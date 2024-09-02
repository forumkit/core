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
        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were started during that period.
        if (empty($secondDate)) {
            $query->whereDate('created_at', $negate ? '!=' : '=', $firstDate);
        } else {
            $query->whereBetween('created_at', [$firstDate, $secondDate], 'and', $negate);
        }
    }
}
