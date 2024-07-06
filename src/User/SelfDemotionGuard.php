<?php

namespace Forumkit\User;

use Forumkit\Group\Group;
use Forumkit\User\Event\Saving;
use Forumkit\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;

class SelfDemotionGuard
{
    /**
     * 阻止管理员通过API移除自己的管理员权限。
     * @param Saving $event
     * @throws PermissionDeniedException
     */
    public function handle(Saving $event)
    {
        // 非管理员用户没有问题
        if (! $event->actor->isAdmin()) {
            return;
        }

        // 只有管理员可以降级其他用户，这意味着降级其他用户是可以的，
        // 因为我们至少还有一个管理员（即执行者）
        if ($event->actor->id !== $event->user->id) {
            return;
        }

        $groups = Arr::get($event->data, 'relationships.groups.data');

        // 如果没有组数据（甚至不是一个空数组），这意味着
        // 组没有被更改（因此也没有被移除） - 我们没问题！
        if (! isset($groups)) {
            return;
        }

        $adminGroups = array_filter($groups, function ($group) {
            return $group['id'] == Group::ADMINISTRATOR_ID;
        });

        // 只要用户仍然是管理员组的一部分，一切就正常
        if ($adminGroups) {
            return;
        }

        // 如果我们到达这一点，我们必须禁止这次编辑
        throw new PermissionDeniedException;
    }
}
