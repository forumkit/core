<?php

namespace Forumkit\Mail;

use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Swift_SmtpTransport;
use Swift_Transport;

class SmtpDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mail_host' => '', // 主机名，IPv4 地址或包含在[]中的IPv6地址
            'mail_port' => '', // 端口号，默认为25
            'mail_encryption' => '', // 加密方式，如 "tls" 或 "ssl"
            'mail_username' => '', // 邮件用户名
            'mail_password' => '', // 邮件密码
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_host' => 'required',
            'mail_port' => 'nullable|integer',
            'mail_encryption' => 'nullable|in:tls,ssl,TLS,SSL',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        $transport = new Swift_SmtpTransport(
            $settings->get('mail_host'),
            $settings->get('mail_port'),
            $settings->get('mail_encryption')
        );

        $transport->setUsername($settings->get('mail_username'));
        $transport->setPassword($settings->get('mail_password'));

        return $transport;
    }
}
