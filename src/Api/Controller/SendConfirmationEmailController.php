<?php

namespace Forumkit\Api\Controller;

use Forumkit\Http\RequestUtil;
use Forumkit\Http\UrlGenerator;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\AccountActivationMailerTrait;
use Forumkit\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendConfirmationEmailController implements RequestHandlerInterface
{
    use AccountActivationMailerTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param \Forumkit\Settings\SettingsRepositoryInterface $settings
     * @param Queue $queue
     * @param UrlGenerator $url
     * @param TranslatorInterface $translator
     */
    public function __construct(SettingsRepositoryInterface $settings, Queue $queue, UrlGenerator $url, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->queue = $queue;
        $this->url = $url;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        if ($actor->id != $id || $actor->is_email_confirmed) {
            throw new PermissionDeniedException;
        }

        $token = $this->generateToken($actor, $actor->email);
        $data = $this->getEmailData($actor, $token);

        $this->sendConfirmationEmail($actor, $data);

        return new EmptyResponse;
    }
}
