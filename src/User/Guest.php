<?php

namespace Forumkit\User;

use Forumkit\Group\Group;

class Guest extends User
{
    /**
     * 覆盖此用户的ID，因为访客没有ID。
     *
     * @var int
     */
    public $id = 0;

    /**
     * 获取访客的组，仅包含 'guests' 组模型。
     *
     * @return \Forumkit\Group\Group
     */
    public function getGroupsAttribute()
    {
        if (! isset($this->attributes['groups'])) {
            $this->attributes['groups'] = $this->relations['groups'] = Group::where('id', Group::GUEST_ID)->get();
        }

        return $this->attributes['groups'];
    }

    /**
     * {@inheritdoc}
     */
    public function isGuest()
    {
        return true;
    }
}
