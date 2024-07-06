<?php

namespace Forumkit\User;

use Forumkit\Mail\Job\SendRawEmailJob;

trait AccountActivationMailerTrait
{
    /**
     * @param User $user
     * @param string $email
     * @return EmailToken
     */
    protected function generateToken(User $user, $email)
    {
        $token = EmailToken::generate($email, $user->id);
        $token->save();

        return $token;
    }

    /**
     * 获取应提供给电子邮件模板的数据。
     *
     * @param User $user
     * @param EmailToken $token
     * @return array
     */
    protected function getEmailData(User $user, EmailToken $token)
    {
        return [
            'username' => $user->display_name,
            'url' => $this->url->to('site')->route('confirmEmail', ['token' => $token->token]),
            'site' => $this->settings->get('site_title')
        ];
    }

    /**
     * @param User $user
     * @param array $data
     */
    protected function sendConfirmationEmail(User $user, $data)
    {
        $body = $this->translator->trans('core.email.activate_account.body', $data);
        $subject = $this->translator->trans('core.email.activate_account.subject');

        $this->queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
