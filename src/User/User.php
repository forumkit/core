<?php

namespace Forumkit\User;

use Carbon\Carbon;
use DomainException;
use Forumkit\Database\AbstractModel;
use Forumkit\Database\ScopeVisibilityTrait;
use Forumkit\Discussion\Discussion;
use Forumkit\Foundation\EventGeneratorTrait;
use Forumkit\Group\Group;
use Forumkit\Group\Permission;
use Forumkit\Http\AccessToken;
use Forumkit\Notification\Notification;
use Forumkit\Post\Post;
use Forumkit\User\DisplayName\DriverInterface;
use Forumkit\User\Event\Activated;
use Forumkit\User\Event\AvatarChanged;
use Forumkit\User\Event\Deleted;
use Forumkit\User\Event\EmailChanged;
use Forumkit\User\Event\EmailChangeRequested;
use Forumkit\User\Event\PasswordChanged;
use Forumkit\User\Event\Registered;
use Forumkit\User\Event\Renamed;
use Forumkit\User\Exception\NotAuthenticatedException;
use Forumkit\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Arr;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

/**
 * @property int $id
 * @property string $username
 * @property string $display_name
 * @property string $email
 * @property bool $is_email_confirmed
 * @property string $password
 * @property string|null $avatar_url
 * @property array $preferences
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property \Carbon\Carbon|null $marked_all_as_read_at
 * @property \Carbon\Carbon|null $read_notifications_at
 * @property int $discussion_count
 * @property int $comment_count
 */
class User extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasEagerLimit;

    /**
     * 将某些属性转换为日期。
     *
     * @var array
     */
    protected $dates = [
        'joined_at',
        'last_seen_at',
        'marked_all_as_read_at',
        'read_notifications_at'
    ];

    /**
     * 用户拥有的权限。
     *
     * @var string[]|null
     */
    protected $permissions = null;

    /**
     * 一个可调用对象数组，在返回之前，通过每个可调用对象传递用户的组列表。
     */
    protected static $groupProcessors = [];

    /**
     * 已注册的用户偏好。每个偏好都有一个键，其值是一个包含以下键的数组：
     *
     * - transformer: 用于限制偏好值的回调
     * - default: 如果未设置偏好，则为默认值
     *
     * @var array
     */
    protected static $preferences = [];

    /**
     * 用于获取显示名称的驱动。
     *
     * @var DriverInterface
     */
    protected static $displayNameDriver;

    /**
     * 用于散列的哈希器。
     *
     * @var Hasher
     */
    protected static $hasher;

    /**
     * 访问网关。
     *
     * @var Access\Gate
     */
    protected static $gate;

    /**
     * 密码检查器。
     *
     * @var array
     */
    protected static $passwordCheckers;

    /**
     * 在 `updateLastSeen()` 更新属性之前的`last_seen`属性值与当前值的差异。以秒为单位。re `updateLastSeen()`
     */
    private const LAST_SEEN_UPDATE_DIFF = 180;

    /**
     * 启动模型。
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // 不允许删除根管理员。
        static::deleting(function (self $user) {
            if ($user->id == 1) {
                throw new DomainException('Cannot delete the root admin');
            }
        });

        static::deleted(function (self $user) {
            $user->raise(new Deleted($user));

            Notification::whereSubject($user)->delete();
        });
    }

    /**
     * 注册新用户。
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return static
     */
    public static function register($username, $email, $password)
    {
        $user = new static;

        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        $user->joined_at = Carbon::now();

        $user->raise(new Registered($user));

        return $user;
    }

    /**
     * @param Access\Gate $gate
     */
    public static function setGate($gate)
    {
        static::$gate = $gate;
    }

    /**
     * 设置显示名称的驱动。
     *
     * @param DriverInterface $driver
     */
    public static function setDisplayNameDriver(DriverInterface $driver)
    {
        static::$displayNameDriver = $driver;
    }

    public static function setPasswordCheckers(array $checkers)
    {
        static::$passwordCheckers = $checkers;
    }

    /**
     * 重命名用户。
     *
     * @param string $username
     * @return $this
     */
    public function rename($username)
    {
        if ($username !== $this->username) {
            $oldUsername = $this->username;
            $this->username = $username;

            $this->raise(new Renamed($this, $oldUsername));
        }

        return $this;
    }

    /**
     * 更改用户的电子邮件。
     *
     * @param string $email
     * @return $this
     */
    public function changeEmail($email)
    {
        if ($email !== $this->email) {
            $this->email = $email;

            $this->raise(new EmailChanged($this));
        }

        return $this;
    }

    /**
     * 请求更改用户的电子邮件。
     *
     * @param string $email
     * @return $this
     */
    public function requestEmailChange($email)
    {
        if ($email !== $this->email) {
            $this->raise(new EmailChangeRequested($this, $email));
        }

        return $this;
    }

    /**
     * 更改用户的密码。
     *
     * @param string $password
     * @return $this
     */
    public function changePassword($password)
    {
        $this->password = $password;

        $this->raise(new PasswordChanged($this));

        return $this;
    }

    /**
     * 设置密码属性，将其存储为哈希。
     *
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? static::$hasher->make($value) : '';
    }

    /**
     * 标记所有讨论为已读。
     *
     * @return $this
     */
    public function markAllAsRead()
    {
        $this->marked_all_as_read_at = Carbon::now();

        return $this;
    }

    /**
     * 标记所有通知为已读。
     *
     * @return $this
     */
    public function markNotificationsAsRead()
    {
        $this->read_notifications_at = Carbon::now();

        return $this;
    }

    /**
     * 更改用户头像的路径。
     *
     * @param string|null $path
     * @return $this
     */
    public function changeAvatarPath($path)
    {
        $this->avatar_url = $path;

        $this->raise(new AvatarChanged($this));

        return $this;
    }

    /**
     * 获取用户的头像URL。
     *
     * @param string|null $value
     * @return string
     */
    public function getAvatarUrlAttribute(string $value = null)
    {
        if ($value && strpos($value, '://') === false) {
            return resolve(Factory::class)->disk('forumkit-avatars')->url($value);
        }

        return $value;
    }

    /**
     * 获取用户的显示名称。
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return static::$displayNameDriver->displayName($this);
    }

    /**
     * 检查给定密码是否与用户的密码匹配。
     *
     * @param string $password
     * @return bool
     */
    public function checkPassword(string $password)
    {
        $valid = false;

        foreach (static::$passwordCheckers as $checker) {
            $result = $checker($this, $password);

            if ($result === false) {
                return false;
            } elseif ($result === true) {
                $valid = true;
            }
        }

        return $valid;
    }

    /**
     * 激活用户的帐户。
     *
     * @return $this
     */
    public function activate()
    {
        if (! $this->is_email_confirmed) {
            $this->is_email_confirmed = true;

            $this->raise(new Activated($this));
        }

        return $this;
    }

    /**
     * 检查用户是否具有某个权限。
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->getPermissions());
    }

    /**
     * 检查用户是否具有类似给定字符串的权限。
     *
     * @param string $match
     * @return bool
     */
    public function hasPermissionLike($match)
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($this->getPermissions() as $permission) {
            if (substr($permission, -strlen($match)) === $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据用户偏好获取应提醒用户的通知类型。
     *
     * @return array
     */
    public function getAlertableNotificationTypes()
    {
        $types = array_keys(Notification::getSubjectModels());

        return array_filter($types, [$this, 'shouldAlert']);
    }

    /**
     * 获取用户未读通知的数量。
     *
     * @return int
     */
    public function getUnreadNotificationCount()
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * 返回尚未读取的所有通知的查询生成器。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function unreadNotifications()
    {
        return $this->notifications()
            ->whereIn('type', $this->getAlertableNotificationTypes())
            ->whereNull('read_at')
            ->where('is_deleted', false)
            ->whereSubjectVisibleTo($this);
    }

    /**
     * 获取所有未读通知。
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getUnreadNotifications()
    {
        return $this->unreadNotifications()->get();
    }

    /**
     * 获取用户的新通知数量。
     *
     * @return int
     */
    public function getNewNotificationCount()
    {
        return $this->unreadNotifications()
            ->where('created_at', '>', $this->read_notifications_at ?? 0)
            ->count();
    }

    /**
     * 通过转换用户存储的首选项并将其与默认值合并，获取该用户的所有已注册首选项的值。
     *
     * @param string|null $value
     * @return array
     */
    public function getPreferencesAttribute($value)
    {
        $defaults = array_map(function ($value) {
            return $value['default'];
        }, static::$preferences);

        $user = $value !== null ? Arr::only((array) json_decode($value, true), array_keys(static::$preferences)) : [];

        return array_merge($defaults, $user);
    }

    /**
     * 对偏好进行编码，以在数据库中存储。
     *
     * @param mixed $value
     */
    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = json_encode($value);
    }

    /**
     * 检查用户是否应接收特定类型的通知。
     *
     * @param string $type
     * @return bool
     */
    public function shouldAlert($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'alert'));
    }

    /**
     * 检查用户是否应接收特定类型的电子邮件通知。
     *
     * @param string $type
     * @return bool
     */
    public function shouldEmail($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'email'));
    }

    /**
     * 获取用户的偏好值。
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference($key, $default = null)
    {
        return Arr::get($this->preferences, $key, $default);
    }

    /**
     * 设置用户的偏好值。
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setPreference($key, $value)
    {
        if (isset(static::$preferences[$key])) {
            $preferences = $this->preferences;

            if (! is_null($transformer = static::$preferences[$key]['transformer'])) {
                $preferences[$key] = call_user_func($transformer, $value);
            } else {
                $preferences[$key] = $value;
            }

            $this->preferences = $preferences;
        }

        return $this;
    }

    /**
     * 将用户标记为刚刚看过。
     *
     * @return $this
     */
    public function updateLastSeen()
    {
        $now = Carbon::now();

        if ($this->last_seen_at === null || $this->last_seen_at->diffInSeconds($now) > User::LAST_SEEN_UPDATE_DIFF) {
            $this->last_seen_at = $now;
        }

        return $this;
    }

    /**
     * 检查用户是否为管理员。
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->groups->contains(Group::ADMINISTRATOR_ID);
    }

    /**
     * 检查用户是否为访客。
     *
     * @return bool
     */
    public function isGuest()
    {
        return false;
    }

    /**
     * 确保当前用户有权执行某项操作。
     *
     * 如果条件不满足，将抛出异常，表示权限不足。
     * 这是关于授权，即重新尝试操作/请求（或使用其他用户帐户）没有改变权限。
     *
     * @param bool $condition
     * @throws PermissionDeniedException
     */
    public function assertPermission($condition)
    {
        if (! $condition) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * 确保给定的参与者已注册。
     *
     * 如果用户是访客，将抛出异常，表示授权失败。
     * 因此，他们可以在登录后（或使用其他认证方式）重试操作。
     *
     * @throws NotAuthenticatedException
     */
    public function assertRegistered()
    {
        if ($this->isGuest()) {
            throw new NotAuthenticatedException;
        }
    }

    /**
     * @param string $ability
     * @param mixed $arguments
     * @throws PermissionDeniedException
     */
    public function assertCan($ability, $arguments = null)
    {
        $this->assertPermission(
            $this->can($ability, $arguments)
        );
    }

    /**
     * @throws PermissionDeniedException
     */
    public function assertAdmin()
    {
        $this->assertCan('administrate');
    }

    /**
     * 定义与用户帖子的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * 定义与用户讨论的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * 定义与用户已读讨论的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Discussion>
     */
    public function read()
    {
        return $this->belongsToMany(Discussion::class);
    }

    /**
     * 定义与用户组的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function visibleGroups()
    {
        return $this->belongsToMany(Group::class)->where('is_hidden', false);
    }

    /**
     * 定义与用户通知的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * 定义与用户电子邮件令牌的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailTokens()
    {
        return $this->hasMany(EmailToken::class);
    }

    /**
     * 定义与用户密码令牌的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function passwordTokens()
    {
        return $this->hasMany(PasswordToken::class);
    }

    /**
     * 定义与用户所在组的所有权限的关系。
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function permissions()
    {
        $groupIds = [Group::GUEST_ID];

        // 如果用户的账户未激活，那么他们基本上只是访客。如果他们已激活，我们可以给他们
        // 标准'成员'组，以及他们被分配到的任何其他组。
        if ($this->is_email_confirmed) {
            $groupIds = array_merge($groupIds, [Group::MEMBER_ID], $this->groups->pluck('id')->all());
        }

        foreach (static::$groupProcessors as $processor) {
            $groupIds = $processor($this, $groupIds);
        }

        return Permission::whereIn('group_id', $groupIds);
    }

    /**
     * 获取用户拥有的权限列表。
     *
     * @return string[]
     */
    public function getPermissions()
    {
        if (is_null($this->permissions)) {
            $this->permissions = $this->permissions()->pluck('permission')->all();
        }

        return $this->permissions;
    }

    /**
     * 定义与用户访问令牌的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    /**
     * 获取用户的登录提供者。
     */
    public function loginProviders()
    {
        return $this->hasMany(LoginProvider::class);
    }

    /**
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function can($ability, $arguments = null)
    {
        return static::$gate->allows($this, $ability, $arguments);
    }

    /**
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = null)
    {
        return ! $this->can($ability, $arguments);
    }

    /**
     * 设置用于散列的哈希器。
     *
     * @param Hasher $hasher
     *
     * @internal
     */
    public static function setHasher(Hasher $hasher)
    {
        static::$hasher = $hasher;
    }

    /**
     * 注册一个带有转换器和默认值的偏好。
     *
     * @param string $key
     * @param callable $transformer
     * @param mixed $default
     *
     * @internal
     */
    public static function registerPreference($key, callable $transformer = null, $default = null)
    {
        static::$preferences[$key] = compact('transformer', 'default');
    }

    /**
     * 注册一个处理用户组列表的回调。
     *
     * @param callable $callback
     * @return void
     *
     * @internal
     */
    public static function addGroupProcessor($callback)
    {
        static::$groupProcessors[] = $callback;
    }

    /**
     * 获取表示用户将通过 $method 接收 $type 通知的偏好键。
     *
     * @param string $type
     * @param string $method
     * @return string
     */
    public static function getNotificationPreferenceKey($type, $method)
    {
        return 'notify_'.$type.'_'.$method;
    }

    /**
     * 刷新用户的评论数量。
     *
     * @return $this
     */
    public function refreshCommentCount()
    {
        $this->comment_count = $this->posts()
            ->where('type', 'comment')
            ->where('is_private', false)
            ->count();

        return $this;
    }

    /**
     * 刷新用户的讨论数量。
     *
     * @return $this
     */
    public function refreshDiscussionCount()
    {
        $this->discussion_count = $this->discussions()
            ->where('is_private', false)
            ->count();

        return $this;
    }
}
