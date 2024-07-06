<?php

namespace Forumkit\Group;

use Forumkit\Database\AbstractModel;
use Forumkit\Database\ScopeVisibilityTrait;
use Forumkit\Foundation\EventGeneratorTrait;
use Forumkit\Group\Event\Created;
use Forumkit\Group\Event\Deleted;
use Forumkit\Group\Event\Renamed;
use Forumkit\User\User;

/**
 * @property int $id
 * @property string $name_singular
 * @property string $name_plural
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_hidden
 * @property \Illuminate\Database\Eloquent\Collection $users
 * @property \Illuminate\Database\Eloquent\Collection $permissions
 */
class Group extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * 管理员组的ID。
     */
    const ADMINISTRATOR_ID = 1;

    /**
     * 游客组的ID。
     */
    const GUEST_ID = 2;

    /**
     * 成员组的ID。
     */
    const MEMBER_ID = 3;

    /**
     * 版主组的ID。
     */
    const MODERATOR_ID = 4;

    /**
     * 应该被转化为日期的属性。
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * 启动模型。
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function (self $group) {
            $group->raise(new Deleted($group));
        });
    }

    /**
     * 创建一个新组。
     *
     * @param string $nameSingular 单数名称
     * @param string $namePlural 复数名称
     * @param string $color 颜色
     * @param string $icon 图标
     * @param bool   $isHidden 是否隐藏
     * @return static
     */
    public static function build($nameSingular, $namePlural, $color = null, $icon = null, bool $isHidden = false): self
    {
        $group = new static;

        $group->name_singular = $nameSingular;
        $group->name_plural = $namePlural;
        $group->color = $color;
        $group->icon = $icon;
        $group->is_hidden = $isHidden;

        $group->raise(new Created($group));

        return $group;
    }

    /**
     * 重命名组。
     *
     * @param string $nameSingular 单数名称
     * @param string $namePlural 复数名称
     * @return $this
     */
    public function rename($nameSingular, $namePlural)
    {
        $this->name_singular = $nameSingular;
        $this->name_plural = $namePlural;

        $this->raise(new Renamed($this));

        return $this;
    }

    /**
     * 定义与组用户的关联关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * 定义与组权限的关联关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * 检查组是否具有某个权限。
     *
     * @param string $permission 权限
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->id == self::ADMINISTRATOR_ID) {
            return true;
        }

        return $this->permissions->contains('permission', $permission);
    }
}
