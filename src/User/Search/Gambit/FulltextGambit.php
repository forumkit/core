<?php

namespace Forumkit\User\Search\Gambit;

use Forumkit\Search\GambitInterface;
use Forumkit\Search\SearchState;
use Forumkit\User\UserRepository;

class FulltextGambit implements GambitInterface
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param \Forumkit\User\UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getUserSearchSubQuery($searchValue)
    {
        return $this->users
            ->query()
            ->select('id')
            ->where('username', 'like', "{$searchValue}%");
    }

    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $searchValue)
    {
        $search->getQuery()
            ->whereIn(
                'id',
                $this->getUserSearchSubQuery($searchValue)
            );

        return true;
    }
}
