<?php

namespace Forumkit\Group;

use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class GroupRepository
{
    /**
     * 为 groups 表获取一个新的查询构建器。
     *
     * @return Builder<Group>
     */
    public function query()
    {
        return Group::query();
    }

    /**
     * 通过ID查找用户，可选地确保它对某个用户可见，否则抛出异常。
     *
     * @param int $id
     * @param User|null $actor
     * @return Group
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    public function queryVisibleTo(?User $actor = null)
    {
        return $this->scopeVisibleTo($this->query(), $actor);
    }

    /**
     * 将查询范围限制为仅包含对用户可见的记录。
     *
     * @param Builder<Group> $query
     * @param User|null $actor
     * @return Builder<Group>
     */
    protected function scopeVisibleTo(Builder $query, ?User $actor = null)
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }
}
