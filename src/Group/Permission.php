<?php

namespace Forumkit\Group;

use Forumkit\Database\AbstractModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $group_id
 * @property string $permission
 */
class Permission extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'group_permission';

    /**
     * 应该被转化为日期的属性
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * 定义这个权限所属的用户组关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 设置保存更新查询的键
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('group_id', $this->group_id)
              ->where('permission', $this->permission);

        return $query;
    }

    /**
     * 获取权限到拥有它们的用户组 ID 的映射
     *
     * @return array[]
     */
    public static function map()
    {
        $permissions = [];

        foreach (static::get() as $permission) {
            $permissions[$permission->permission][] = (string) $permission->group_id;
        }

        return $permissions;
    }
}
