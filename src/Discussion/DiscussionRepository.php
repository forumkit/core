<?php

namespace Forumkit\Discussion;

use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class DiscussionRepository
{
    /**
     * 获取针对讨论表的新的查询构建器。
     *
     * @return Builder<Discussion>
     */
    public function query()
    {
        return Discussion::query();
    }

    /**
     * 通过ID查找讨论，可选择性地确保它对某个用户是可见的，否则抛出异常。
     *
     * @param int|string $id
     * @param User|null $user
     * @return \Forumkit\Discussion\Discussion
     */
    public function findOrFail($id, User $user = null)
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $user)->firstOrFail();
    }

    /**
     * 获取用户已完全阅读的讨论ID。
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection<Discussion>
     * @deprecated 1.3 Use `getReadIdsQuery` instead
     */
    public function getReadIds(User $user)
    {
        return $this->getReadIdsQuery($user)->get();
    }

    /**
     * 获取包含用户已完全阅读的讨论ID的查询。
     *
     * @param User $user
     * @return Builder<Discussion>
     */
    public function getReadIdsQuery(User $user): Builder
    {
        return $this->query()
            ->leftJoin('discussion_user', 'discussion_user.discussion_id', '=', 'discussions.id')
            ->where('discussion_user.user_id', $user->id)
            ->whereColumn('last_read_post_number', '>=', 'last_post_number')
            ->select('id');
    }

    /**
     * 对查询范围进行限定，仅包括对用户可见的记录。
     *
     * @param Builder<Discussion> $query
     * @param User|null $user
     * @return Builder<Discussion>
     */
    protected function scopeVisibleTo(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }
}
