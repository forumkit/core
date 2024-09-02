<?php

namespace Forumkit\Notification\Driver;

use Forumkit\Notification\Blueprint\BlueprintInterface;
use Forumkit\Notification\Job\SendNotificationsJob;
use Forumkit\User\User;
use Illuminate\Contracts\Queue\Queue;

class AlertNotificationDriver implements NotificationDriverInterface
{
    /**
     * @var Queue
     */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * {@inheritDoc}
     */
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (count($users)) {
            $this->queue->push(new SendNotificationsJob($blueprint, $users));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        User::registerPreference(
            User::getNotificationPreferenceKey($blueprintClass::getType(), 'alert'),
            'boolval',
            in_array('alert', $driversEnabledByDefault)
        );
    }
}
