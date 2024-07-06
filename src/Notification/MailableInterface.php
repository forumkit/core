<?php

namespace Forumkit\Notification;

use Symfony\Contracts\Translation\TranslatorInterface;

interface MailableInterface
{
    /**
     * 获取用于构建通知电子邮件的视图名称。
     *
     * @return string|array
     */
    public function getEmailView();

    /**
     * 获取通知电子邮件的主题行。
     *
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getEmailSubject(TranslatorInterface $translator);
}
