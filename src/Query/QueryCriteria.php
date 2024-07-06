<?php

namespace Forumkit\Query;

use Forumkit\User\User;

/**
 * 表示确定查询完整结果集的准则。不包含 limit 和 offset，因为它们仅确定返回整个结果集的哪一部分。
 */
class QueryCriteria
{
    /**
     * 执行查询的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 查询参数。
     *
     * @var array
     */
    public $query;

    /**
     * 一个排序顺序对数组，其中列是键，顺序是值。顺序可以是 'asc'、'desc' 或一个按 ID 排序的数组。
     *
     * @var array
     */
    public $sort;

    /**
     * 对于此请求的排序是否来自控制器的默认排序？
     * 如果为 false，则当前请求指定了排序。
     *
     * @var bool
     */
    public $sortIsDefault;

    /**
     * @param User $actor 执行查询的用户
     * @param array $query 查询参数
     * @param array $sort 一个排序顺序对数组，其中列是键，顺序是值。顺序可以是 'asc'、'desc' 或一个按 ID 排序的数组
     */
    public function __construct(User $actor, $query, array $sort = null, bool $sortIsDefault = false)
    {
        $this->actor = $actor;
        $this->query = $query;
        $this->sort = $sort;
        $this->sortIsDefault = $sortIsDefault;
    }
}
