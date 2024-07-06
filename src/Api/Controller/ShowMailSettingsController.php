<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\MailSettingsSerializer;
use Forumkit\Http\RequestUtil;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowMailSettingsController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = MailSettingsSerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $drivers = array_map(function ($driver) {
            return self::$container->make($driver);
        }, self::$container->make('mail.supported_drivers'));

        $settings = self::$container->make(SettingsRepositoryInterface::class);
        $configured = self::$container->make('forumkit.mail.configured_driver');
        $actual = self::$container->make('mail.driver');
        $validator = self::$container->make(Factory::class);

        $errors = $configured->validate($settings, $validator);

        return [
            'drivers' => $drivers,
            'sending' => $actual->canSend(),
            'errors' => $errors,
        ];
    }
}
