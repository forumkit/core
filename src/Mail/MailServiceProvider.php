<?php

namespace Forumkit\Mail;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Arr;
use Swift_Mailer;

class MailServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('mail.supported_drivers', function () {
            return [
                'mail' => SendmailDriver::class,
                'mailgun' => MailgunDriver::class,
                'log' => LogDriver::class,
                'smtp' => SmtpDriver::class,
            ];
        });

        $this->container->singleton('mail.driver', function (Container $container) {
            $configured = $container->make('forumkit.mail.configured_driver');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $validator = $container->make(Factory::class);

            return $configured->validate($settings, $validator)->any()
                ? $container->make(NullDriver::class)
                : $configured;
        });

        $this->container->alias('mail.driver', DriverInterface::class);

        $this->container->singleton('forumkit.mail.configured_driver', function (Container $container) {
            $drivers = $container->make('mail.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('mail_driver');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $container->make($driverClass)
                : $container->make(NullDriver::class);
        });

        $this->container->singleton('swift.mailer', function (Container $container) {
            return new Swift_Mailer(
                $container->make('mail.driver')->buildTransport(
                    $container->make(SettingsRepositoryInterface::class)
                )
            );
        });

        $this->container->singleton('mailer', function (Container $container) {
            $mailer = new Mailer(
                'forumkit',
                $container['view'],
                $container['swift.mailer'],
                $container['events']
            );

            if ($container->bound('queue')) {
                $mailer->setQueue($container->make('queue'));
            }

            $settings = $container->make(SettingsRepositoryInterface::class);
            $mailer->alwaysFrom($settings->get('mail_from'), $settings->get('site_name'));

            return $mailer;
        });
    }
}
