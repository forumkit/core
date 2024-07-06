<?php

namespace Forumkit\Search;

use Forumkit\Query\AbstractQueryState;

class SearchState extends AbstractQueryState
{
    /**
     * @var GambitInterface[]
     */
    protected $activeGambits = [];

    /**
     * 获取此搜索中活跃的策略列表。
     *
     * @return GambitInterface[]
     */
    public function getActiveGambits()
    {
        return $this->activeGambits;
    }

    /**
     * 将 gambit 添加为在此搜索中处于活动状态。
     *
     * @param GambitInterface $gambit
     * @return void
     */
    public function addActiveGambit(GambitInterface $gambit)
    {
        $this->activeGambits[] = $gambit;
    }
}
