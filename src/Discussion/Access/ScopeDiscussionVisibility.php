<?php

namespace Forumkit\Discussion\Access;

use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeDiscussionVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->cannot('viewSite')) {
            $query->whereRaw('FALSE');

            return;
        }

        // 默认隐藏私有讨论。
        $query->where(function ($query) use ($actor) {
            $query->where('discussions.is_private', false)
            ->orWhere(function ($query) use ($actor) {
                $query->whereVisibleTo($actor, 'viewPrivate');
            });
        });

        // 隐藏隐藏的讨论，除非这些讨论是由当前用户创建的，或者当前用户有权查看隐藏的讨论。
        if (! $actor->hasPermission('discussion.hide')) {
            $query->where(function ($query) use ($actor) {
                $query->whereNull('discussions.hidden_at')
                ->orWhere('discussions.user_id', $actor->id)
                    ->orWhere(function ($query) use ($actor) {
                        $query->whereVisibleTo($actor, 'hide');
                    });
            });
        }

        // 隐藏没有评论的讨论，除非这些讨论是由当前用户创建的，或者用户被允许编辑讨论的帖子。
        if (! $actor->hasPermission('discussion.editPosts')) {
            $query->where(function ($query) use ($actor) {
                $query->where('discussions.comment_count', '>', 0)
                    ->orWhere('discussions.user_id', $actor->id)
                    ->orWhere(function ($query) use ($actor) {
                        $query->whereVisibleTo($actor, 'editPosts');
                    });
            });
        }
    }
}
