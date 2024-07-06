<?php

namespace Forumkit\Query;

use Forumkit\User\User;
use Illuminate\Database\Query\Builder;

abstract class AbstractQueryState
{
    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var User
     */
    protected $actor;

    /**
     * @var mixed
     */
    protected $defaultSort = [];

    /**
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Builder $query, User $actor, $defaultSort = [])
    {
        $this->query = $query;
        $this->actor = $actor;
        $this->defaultSort = $defaultSort;
    }

    /**
     * 获取用于搜索结果查询的查询构建器。
     *
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * 获取正在执行搜索的用户。
     *
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * 获取搜索的默认排序顺序。
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    /**
     * 设置搜索的默认排序顺序。这仅在搜索条件中未指定排序顺序时应用。
     *
     * @param mixed $defaultSort 一个排序顺序对数组，其中列是键，顺序是值。顺序可以是 'asc'、'desc' 或一个按 ID 排序的数组。
     * @return mixed
     */
    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }
}
