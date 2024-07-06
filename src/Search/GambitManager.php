<?php

namespace Forumkit\Search;

use LogicException;

/**
 * @internal
 */
class GambitManager
{
    /**
     * @var array
     */
    protected $gambits = [];

    /**
     * @var GambitInterface
     */
    protected $fulltextGambit;

    public function __construct(GambitInterface $fulltextGambit)
    {
        $this->fulltextGambit = $fulltextGambit;
    }

    /**
     * 添加一个策略。
     *
     * @param GambitInterface $gambit
     */
    public function add(GambitInterface $gambit)
    {
        $this->gambits[] = $gambit;
    }

    /**
     * 在给定搜索查询的情况下，将 gambits 应用于搜索。
     *
     * @param SearchState $search
     * @param string $query
     */
    public function apply(SearchState $search, $query)
    {
        $query = $this->applyGambits($search, $query);

        if ($query) {
            $this->applyFulltext($search, $query);
        }
    }

    /**
     * 将搜索查询分解为位数组。
     *
     * @param string $query
     * @return array
     */
    protected function explode($query)
    {
        return str_getcsv($query, ' ');
    }

    /**
     * @param SearchState $search
     * @param string $query
     * @return string
     */
    protected function applyGambits(SearchState $search, $query)
    {
        $bits = $this->explode($query);

        if (! $bits) {
            return '';
        }

        foreach ($bits as $k => $bit) {
            foreach ($this->gambits as $gambit) {
                if (! $gambit instanceof GambitInterface) {
                    throw new LogicException(
                        'Gambit '.get_class($gambit).' does not implement '.GambitInterface::class
                    );
                }

                if ($gambit->apply($search, $bit)) {
                    $search->addActiveGambit($gambit);
                    unset($bits[$k]);
                    break;
                }
            }
        }

        return implode(' ', $bits);
    }

    /**
     * @param SearchState $search
     * @param string $query
     */
    protected function applyFulltext(SearchState $search, $query)
    {
        $search->addActiveGambit($this->fulltextGambit);
        $this->fulltextGambit->apply($search, $query);
    }
}
