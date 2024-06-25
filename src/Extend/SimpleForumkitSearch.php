<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class SimpleForumkitSearch implements ExtenderInterface
{
    private $fullTextGambit;
    private $gambits = [];
    private $searcher;
    private $searchMutators = [];

    /**
     * @param string $searcherClass: The ::class attribute of the Searcher you are modifying.
     *                               This searcher must extend \Forumkit\Search\AbstractSearcher.
     */
    public function __construct(string $searcherClass)
    {
        $this->searcher = $searcherClass;
    }

    /**
     * Add a gambit to this searcher. Gambits are used to filter search queries.
     *
     * @param string $gambitClass: The ::class attribute of the gambit you are adding.
     *                             This gambit must extend \Forumkit\Search\AbstractRegexGambit
     * @return self
     */
    public function addGambit(string $gambitClass): self
    {
        $this->gambits[] = $gambitClass;

        return $this;
    }

    /**
     * Set the full text gambit for this searcher. The full text gambit actually executes the search.
     *
     * @param string $gambitClass: The ::class attribute of the full test gambit you are adding.
     *                             This gambit must implement \Forumkit\Search\GambitInterface
     * @return self
     */
    public function setFullTextGambit(string $gambitClass): self
    {
        $this->fullTextGambit = $gambitClass;

        return $this;
    }

    /**
     * Add a callback through which to run all search queries after gambits have been applied.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - \Forumkit\Search\SearchState $search
     * - \Forumkit\Query\QueryCriteria $criteria
     *
     * The callback should return void.
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
