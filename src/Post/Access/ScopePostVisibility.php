<?php

namespace Forumkit\Post\Access;

use Forumkit\Discussion\Discussion;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopePostVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        // 确保帖子的讨论也是可见的。
        $query->whereExists(function ($query) use ($actor) {
            $query->selectRaw('1')
                ->from('discussions')
                ->whereColumn('discussions.id', 'posts.discussion_id');
            Discussion::query()->setQuery($query)->whereVisibleTo($actor);
        });

        // 默认隐藏私有帖子。
        $query->where(function ($query) use ($actor) {
            $query->where('posts.is_private', false)
                ->orWhere(function ($query) use ($actor) {
                    $query->whereVisibleTo($actor, 'viewPrivate');
                });
        });

        // 隐藏隐藏的帖子，除非它们是当前用户发布的，或者
        // 当前用户有权限查看讨论中的隐藏帖子。
        if (! $actor->hasPermission('discussion.hidePosts')) {
            $query->where(function ($query) use ($actor) {
                $query->whereNull('posts.hidden_at')
                ->orWhere('posts.user_id', $actor->id)
                    ->orWhereExists(function ($query) use ($actor) {
                        $query->selectRaw('1')
                            ->from('discussions')
                            ->whereColumn('discussions.id', 'posts.discussion_id')
                            ->where(function ($query) use ($actor) {
                                $query
                                    ->whereRaw('1=0')
                                    ->orWhere(function ($query) use ($actor) {
                                        Discussion::query()->setQuery($query)->whereVisibleTo($actor, 'hidePosts');
                                    });
                            });
                    });
            });
        }
    }
}
