<?php

namespace Forumkit\Forum\Controller;

use DateTime;
use Forumkit\Http\Controller\AbstractHtmlController;
use Forumkit\User\Exception\InvalidConfirmationTokenException;
use Forumkit\User\PasswordToken;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResetPasswordController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     * @throws \Forumkit\User\Exception\InvalidConfirmationTokenException
     */
    public function render(Request $request)
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $token = PasswordToken::findOrFail($token);

        if ($token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $this->view->make('forumkit.forum::reset-password')
            ->with('passwordToken', $token->token)
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
