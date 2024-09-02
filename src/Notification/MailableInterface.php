<?php

namespace Forumkit\Notification;

use Symfony\Contracts\Translation\TranslatorInterface;

interface MailableInterface
{
    /**
     * Get the name of the view to construct a notification email with.
     *
     * @return string|array
     */
    public function getEmailView();

    /**
     * Get the subject line for a notification email.
     *
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getEmailSubject(TranslatorInterface $translator);
}
