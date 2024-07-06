<?php

namespace Forumkit\Post;

use Forumkit\Discussion\Discussion;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class PostRepository
{
    /**
     * 获取 posts 表的新查询生成器。
     *
     * @return Builder<Post>
     */
    public function query()
    {
        return Post::query();
    }

    /**
     * @param User|null $user
     * @return Builder<Post>
     */
    public function queryVisibleTo(?User $user = null)
    {
        $query = $this->query();

        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }

    /**
     * 根据ID查找帖子，可选地确保它对某个用户可见，否则抛出异常。
     *
     * @param int $id 帖子ID
     * @param \Forumkit\User\User|null $actor 用户实例
     * @return Post 帖子对象
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        return $this->queryVisibleTo($actor)->findOrFail($id);
    }

    /**
     * 根据某些条件查找帖子，可选地确保它们对某个用户可见，并使用其他标准。
     *
     * @param array $where 查询条件数组
     * @param \Forumkit\User\User|null $actor 用户实例
     * @param array $sort 排序标准数组
     * @param int $count 返回的数量
     * @param int $start 开始的索引
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere(array $where = [], User $actor = null, $sort = [], $count = null, $start = 0)
    {
        $query = $this->queryVisibleTo($actor)
            ->where($where)
            ->skip($start)
            ->take($count);

        foreach ((array) $sort as $field => $order) {
            $query->orderBy($field, $order);
        }

        return $query->get();
    }

    /**
     * 过滤帖子ID列表，仅包括对某个用户可见的帖子。
     *
     * @param array $ids 帖子ID数组
     * @param User $actor 用户实例
     * @return array 过滤后的帖子ID数组
     */
    public function filterVisibleIds(array $ids, User $actor)
    {
        return $this->queryIds($ids, $actor)->pluck('posts.id')->all();
    }

    /**
     * 获取某个讨论中某个编号的帖子的位置。如果该编号的帖子不存在，将返回最接近它的帖子的索引。
     *
     * @param int $discussionId 讨论ID
     * @param int $number 帖子编号
     * @param \Forumkit\User\User|null $actor 用户实例
     * @return int 帖子索引
     */
    public function getIndexForNumber($discussionId, $number, User $actor = null)
    {
        if (! ($discussion = Discussion::find($discussionId))) {
            return 0;
        }

        $query = $discussion->posts()
            ->whereVisibleTo($actor)
            ->where('created_at', '<', function ($query) use ($discussionId, $number) {
                $query->select('created_at')
                    ->from('posts')
                    ->where('discussion_id', $discussionId)
                    ->whereNotNull('number')
                    ->take(1)

                    // 我们不将$number作为绑定添加，因为这样做会使绑定顺序出错。
                    ->orderByRaw('ABS(CAST(number AS SIGNED) - '.(int) $number.')');
            });

        return $query->count();
    }

    /**
     * @param array $ids 帖子ID数组
     * @param User|null $actor 用户实例
     * @return Builder<Post> 查询构建器
     */
    protected function queryIds(array $ids, User $actor = null)
    {
        return $this->queryVisibleTo($actor)->whereIn('posts.id', $ids);
    }
}
