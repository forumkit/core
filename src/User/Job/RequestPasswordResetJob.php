<?php

namespace Forumkit\User\Job;

use Forumkit\Http\UrlGenerator;
use Forumkit\Mail\Job\SendRawEmailJob;
use Forumkit\Queue\AbstractJob;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\PasswordToken;
use Forumkit\User\UserRepository;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestPasswordResetJob extends AbstractJob
{
    /**
     * @var string
     */
    protected $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        TranslatorInterface $translator,
        UserRepository $users,
        Queue $queue
    ) {
        $user = $users->findByEmail($this->email);

        if (! $user) {
            return;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        $data = [
            'username' => $user->display_name,
            'url' => $url->to('forum')->route('resetPassword', ['token' => $token->token]),
            'forum' => $settings->get('forum_title'),
        ];

        $body = $translator->trans('core.email.reset_password.body', $data);
        $subject = $translator->trans('core.email.reset_password.subject');

        $queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
