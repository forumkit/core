<?php

namespace Forumkit\Mail;

use Forumkit\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Support\MessageBag;
use Swift_Transport;

class MailgunDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mail_mailgun_secret' => '', // 密钥
            'mail_mailgun_domain' => '', // API 基础 URL
            'mail_mailgun_region' => [ // 区域的端点
                'api.mailgun.net' => 'US', // 美国
                'api.eu.mailgun.net' => 'EU', // 欧洲
            ],
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_mailgun_secret' => 'required',
            'mail_mailgun_domain' => 'required|regex:/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/',
            'mail_mailgun_region' => 'required|in:api.mailgun.net,api.eu.mailgun.net',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new MailgunTransport(
            new Client(['connect_timeout' => 60]),
            $settings->get('mail_mailgun_secret'),
            $settings->get('mail_mailgun_domain'),
            $settings->get('mail_mailgun_region')
        );
    }
}
