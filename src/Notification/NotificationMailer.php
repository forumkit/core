<?php

namespace Forumkit\Notification;

use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Mail\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationMailer
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var TranslatorInterface&Translator
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param TranslatorInterface&Translator $translator
     */
    public function __construct(Mailer $mailer, TranslatorInterface $translator, SettingsRepositoryInterface $settings)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    /**
     * @param MailableInterface $blueprint
     * @param User $user
     */
    public function send(MailableInterface $blueprint, User $user)
    {
        // 确保通知以用户的默认语言发送，如果用户选择了语言。
        // 如果所选的语言不再可用，则使用站点的默认语言。
        $this->translator->setLocale($user->getPreference('locale') ?? $this->settings->get('default_locale'));

        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('blueprint', 'user'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->display_name)
                        ->subject($blueprint->getEmailSubject($this->translator));
            }
        );
    }
}
