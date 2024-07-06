<?php

namespace Forumkit\Notification\Job;

use Forumkit\Notification\MailableInterface;
use Forumkit\Notification\NotificationMailer;
use Forumkit\Queue\AbstractJob;
use Forumkit\User\User;

class SendEmailNotificationJob extends AbstractJob
{
    /**
     * @var MailableInterface
     */
    private $blueprint;

    /**
     * @var User
     */
    private $recipient;

    public function __construct(MailableInterface $blueprint, User $recipient)
    {
        $this->blueprint = $blueprint;
        $this->recipient = $recipient;
    }

    public function handle(NotificationMailer $mailer)
    {
        $mailer->send($this->blueprint, $this->recipient);
    }
}
