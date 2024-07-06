<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Forumkit\Notification\NotificationSyncer;
use Illuminate\Contracts\Container\Container;

class Notification implements ExtenderInterface
{
    private $blueprints = [];
    private $serializers = [];
    private $drivers = [];
    private $typesEnabledByDefault = [];
    private $beforeSendingCallbacks = [];

    /**
     * 注册一种通知类型。
     * 
     * @param string $blueprint: 蓝图类的::class属性
     *                          该蓝图应实现\Forumkit\Notification\Blueprint\BlueprintInterface接口
     * @param string $serializer: 序列化类的::class属性
     *                           该序列化器应继承自 \Forumkit\Api\Serializer\AbstractSerializer
     * @param string[] $driversEnabledByDefault: 默认情况下为此通知类型启用的驱动名称数组
     *                                       (例如: alert, email)
     * @return self
     */
    public function type(string $blueprint, string $serializer, array $driversEnabledByDefault = []): self
    {
        $this->blueprints[$blueprint] = $driversEnabledByDefault;
        $this->serializers[$blueprint::getType()] = $serializer;

        return $this;
    }

    /**
     * 注册一个通知驱动。
     * 
     * @param string $driverName: 通知驱动的名称
     * @param string $driver: 驱动类的::class属性
     *                       该驱动应实现\Forumkit\Notification\Driver\NotificationDriverInterface 接口
     * @param string[] $typesEnabledByDefault: 默认情况下为此驱动启用的蓝图类名称数组
     * @return self
     */
    public function driver(string $driverName, string $driver, array $typesEnabledByDefault = []): self
    {
        $this->drivers[$driverName] = $driver;
        $this->typesEnabledByDefault[$driverName] = $typesEnabledByDefault;

        return $this;
    }

    /**
     * 设置一个回调函数，用于处理接收者筛选逻辑。
     * 
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - \Forumkit\Notification\Blueprint\BlueprintInterface $blueprint 蓝图对象
     * - \Forumkit\User\User[] $newRecipients 潜在的新接收者数组
     *
     * 回调函数应返回一个接收者数组
     * - \Forumkit\User\User[] $newRecipients
     *
     * @return self
     */
    public function beforeSending($callback): self
    {
        $this->beforeSendingCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.notification.blueprints', function ($existingBlueprints) {
            $existingBlueprints = array_merge($existingBlueprints, $this->blueprints);

            foreach ($this->typesEnabledByDefault as $driverName => $typesEnabledByDefault) {
                foreach ($typesEnabledByDefault as $blueprintClass) {
                    if (isset($existingBlueprints[$blueprintClass]) && (! in_array($driverName, $existingBlueprints[$blueprintClass]))) {
                        $existingBlueprints[$blueprintClass][] = $driverName;
                    }
                }
            }

            return $existingBlueprints;
        });

        $container->extend('forumkit.api.notification_serializers', function ($existingSerializers) {
            return array_merge($existingSerializers, $this->serializers);
        });

        $container->extend('forumkit.notification.drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });

        foreach ($this->beforeSendingCallbacks as $callback) {
            NotificationSyncer::beforeSending(ContainerUtil::wrapCallback($callback, $container));
        }
    }
}
