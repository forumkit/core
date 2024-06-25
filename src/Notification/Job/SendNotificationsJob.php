<?php

namespace Forumkit\Notification\Job;

use Forumkit\Notification\Blueprint\BlueprintInterface;
use Forumkit\Notification\Notification;
use Forumkit\Queue\AbstractJob;
use Forumkit\User\User;

class SendNotificationsJob extends AbstractJob
{
    /**
     * @var BlueprintInterface
     */
    private $blueprint;

    /**
     * @var User[]
     */
    private $recipients;

    public function __construct(BlueprintInterface $blueprint, array $recipients = [])
    {
        $this->blueprint = $blueprint;
        $this->recipients = $recipients;
    }

    public function handle()
    {
        Notification::notify($this->recipients, $this->blueprint);
    }
}
