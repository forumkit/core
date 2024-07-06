<?php

namespace Forumkit\Notification;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Notification\Blueprint\DiscussionRenamedBlueprint;
use Illuminate\Contracts\Container\Container;

class NotificationServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('forumkit.notification.drivers', function () {
            return [
                'alert' => Driver\AlertNotificationDriver::class,
                'email' => Driver\EmailNotificationDriver::class,
            ];
        });

        $this->container->singleton('forumkit.notification.blueprints', function () {
            return [
                DiscussionRenamedBlueprint::class => ['alert']
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container)
    {
        $this->setNotificationDrivers($container);
        $this->setNotificationTypes($container);
    }

    /**
     * 注册通知驱动程序。
     */
    protected function setNotificationDrivers(Container $container)
    {
        foreach ($container->make('forumkit.notification.drivers') as $driverName => $driver) {
            NotificationSyncer::addNotificationDriver($driverName, $container->make($driver));
        }
    }

    /**
     * 注册通知类型。
     */
    protected function setNotificationTypes(Container $container)
    {
        $blueprints = $container->make('forumkit.notification.blueprints');

        foreach ($blueprints as $blueprint => $driversEnabledByDefault) {
            $this->addType($blueprint, $driversEnabledByDefault);
        }
    }

    protected function addType(string $blueprint, array $driversEnabledByDefault)
    {
        Notification::setSubjectModel(
            $type = $blueprint::getType(),
            $blueprint::getSubjectModel()
        );

        foreach (NotificationSyncer::getNotificationDrivers() as $driverName => $driver) {
            $driver->registerType(
                $blueprint,
                $driversEnabledByDefault
            );
        }
    }
}
