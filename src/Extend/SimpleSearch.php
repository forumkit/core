<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class SimpleSearch implements ExtenderInterface
{
    private $fullTextGambit;
    private $gambits = [];
    private $searcher;
    private $searchMutators = [];

    /**
     * @param string $searcherClass: 您要修改的搜索器的 ::class 属性
     *                               这个搜索器必须扩展 \Forumkit\Search\AbstractSearcher.
     */
    public function __construct(string $searcherClass)
    {
        $this->searcher = $searcherClass;
    }

    /**
     * 向此搜索器添加一个过滤器（gambit）。过滤器用于过滤搜索查询。
     *
     * @param string $gambitClass: 您要添加的过滤器的 ::class 属性
     *                             这个过滤器必须扩展 \Forumkit\Search\AbstractRegexGambit
     * @return self
     */
    public function addGambit(string $gambitClass): self
    {
        $this->gambits[] = $gambitClass;

        return $this;
    }

    /**
     * 为此搜索器设置全文过滤器。全文过滤器实际执行搜索。
     *
     * @param string $gambitClass: 您要添加的全文过滤器的 ::class 属性
     *                             这个过滤器必须实现 \Forumkit\Search\GambitInterface
     * @return self
     */
    public function setFullTextGambit(string $gambitClass): self
    {
        $this->fullTextGambit = $gambitClass;

        return $this;
    }

    /**
     * 添加一个回调函数，用于在过滤器应用后对所有搜索查询进行处理。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用的类，并应接受以下参数：
     * - \Forumkit\Search\SearchState $search 搜索状态对象
     * - \Forumkit\Query\QueryCriteria $criteria 查询条件对象
     *
     * 回调函数应返回 void（无返回值）。
     *
     * @return self
     */
    public function addSearchMutator($callback): self
    {
        $this->searchMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! is_null($this->fullTextGambit)) {
            $container->extend('forumkit.simple_search.fulltext_gambits', function ($oldFulltextGambits) {
                $oldFulltextGambits[$this->searcher] = $this->fullTextGambit;

                return $oldFulltextGambits;
            });
        }

        $container->extend('forumkit.simple_search.gambits', function ($oldGambits) {
            foreach ($this->gambits as $gambit) {
                $oldGambits[$this->searcher][] = $gambit;
            }

            return $oldGambits;
        });

        $container->extend('forumkit.simple_search.search_mutators', function ($oldMutators) {
            foreach ($this->searchMutators as $mutator) {
                $oldMutators[$this->searcher][] = $mutator;
            }

            return $oldMutators;
        });
    }
}
