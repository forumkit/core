<?php

namespace Forumkit\Notification;

use Forumkit\Notification\Blueprint\BlueprintInterface;
use Forumkit\Notification\Driver\NotificationDriverInterface;
use Forumkit\User\User;

/**
 * 通知同步器将通知蓝图提交到数据库，并根据用户偏好通过电子邮件发送。
 * 蓝图代表单个通知，同步器将其与特定用户关联，并在其收件箱中使其可用。
 */
class NotificationSyncer
{
    /**
     * 是否将通知限制为每个用户一个。
     *
     * @var bool
     */
    protected static $onePerUser = false;

    /**
     * 已发送通知的用户ID的内部列表。
     *
     * @var int[]
     */
    protected static $sentTo = [];

    /**
     * 通知驱动程序的映射。
     *
     * @var NotificationDriverInterface[]
     */
    protected static $notificationDrivers = [];

    /**
     * @var array
     */
    protected static $beforeSendingCallbacks = [];

    /**
     * 同步通知，使其对指定用户可见，而对其他用户不可见。
     * 如果这是首次使其可见，则尝试向用户发送电子邮件。
     *
     * @param \Forumkit\Notification\Blueprint\BlueprintInterface $blueprint
     * @param User[] $users
     * @return void
     */
    public function sync(Blueprint\BlueprintInterface $blueprint, array $users)
    {
        // 在数据库中查找与此蓝图匹配的所有现有通知记录。
        // 我们首先假设它们都需要被删除，以便与提供的用户列表匹配。
        $toDelete = Notification::matchingBlueprint($blueprint)->get();
        $toUndelete = [];
        $newRecipients = [];

        // 对于提供的每个用户，检查数据库中是否已存在该用户的通知记录。
        // 如果存在，则确保其未被标记为已删除。
        // 如果不存在，我们想要为他们创建一个新记录。
        foreach ($users as $user) {
            if (! ($user instanceof User)) {
                continue;
            }

            $existing = $toDelete->first(function ($notification) use ($user) {
                return $notification->user_id === $user->id;
            });

            if ($existing) {
                $toUndelete[] = $existing->id;
                $toDelete->forget($toDelete->search($existing));
            } elseif (! static::$onePerUser || ! in_array($user->id, static::$sentTo)) {
                $newRecipients[] = $user;
                static::$sentTo[] = $user->id;
            }
        }

        // 删除集合中剩余的通知记录（即未通过上述循环移除的记录）。
        // 恢复我们想要保留的现有记录。
        if (count($toDelete)) {
            $this->setDeleted($toDelete->pluck('id')->all(), true);
        }

        if (count($toUndelete)) {
            $this->setDeleted($toUndelete, false);
        }

        foreach (static::$beforeSendingCallbacks as $callback) {
            $newRecipients = $callback($blueprint, $newRecipients);
        }

        // 为所有首次接收此通知的用户（我们知道他们在数据库中没有记录）创建一个通知记录，并发送电子邮件。
        // 由于这两个操作都可能会消耗大量资源（数据库和邮件服务器），我们将它们放入队列中执行。
        foreach (static::getNotificationDrivers() as $driverName => $driver) {
            $driver->send($blueprint, $newRecipients);
        }
    }

    /**
     * 为所有用户删除一个通知。
     *
     * @param \Forumkit\Notification\Blueprint\BlueprintInterface $blueprint
     * @return void
     */
    public function delete(BlueprintInterface $blueprint)
    {
        Notification::matchingBlueprint($blueprint)->update(['is_deleted' => true]);
    }

    /**
     * 为所有用户恢复一个通知。
     *
     * @param BlueprintInterface $blueprint
     * @return void
     */
    public function restore(BlueprintInterface $blueprint)
    {
        Notification::matchingBlueprint($blueprint)->update(['is_deleted' => false]);
    }

    /**
     * 在给定回调的整个持续时间内，将通知限制为每个用户一个。
     *
     * @param callable $callback
     * @return void
     */
    public function onePerUser(callable $callback)
    {
        static::$sentTo = [];
        static::$onePerUser = true;

        $callback();

        static::$onePerUser = false;
    }

    /**
     * 设置一组通知记录的已删除状态。
     *
     * @param int[] $ids
     * @param bool $isDeleted
     */
    protected function setDeleted(array $ids, $isDeleted)
    {
        Notification::whereIn('id', $ids)->update(['is_deleted' => $isDeleted]);
    }

    /**
     * 将通知驱动程序添加到列表中。
     *
     * @param string $driverName
     * @param NotificationDriverInterface $driver
     *
     * @internal
     */
    public static function addNotificationDriver(string $driverName, NotificationDriverInterface $driver): void
    {
        static::$notificationDrivers[$driverName] = $driver;
    }

    /**
     * @return NotificationDriverInterface[]
     */
    public static function getNotificationDrivers(): array
    {
        return static::$notificationDrivers;
    }

    /**
     * @param callable|string $callback
     *
     * @internal
     */
    public static function beforeSending($callback): void
    {
        static::$beforeSendingCallbacks[] = $callback;
    }
}
