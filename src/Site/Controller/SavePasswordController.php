<?php

namespace Forumkit\Site\Controller;

use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\Http\SessionAccessToken;
use Forumkit\Http\SessionAuthenticator;
use Forumkit\Http\UrlGenerator;
use Forumkit\User\PasswordToken;
use Forumkit\User\UserValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class SavePasswordController implements RequestHandlerInterface
{
    use DispatchEventsTrait;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var \Forumkit\User\UserValidator
     */
    protected $validator;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @param UrlGenerator $url
     * @param SessionAuthenticator $authenticator
     * @param UserValidator $validator
     * @param Factory $validatorFactory
     */
    public function __construct(UrlGenerator $url, SessionAuthenticator $authenticator, UserValidator $validator, Factory $validatorFactory, Dispatcher $events)
    {
        $this->url = $url;
        $this->authenticator = $authenticator;
        $this->validator = $validator;
        $this->validatorFactory = $validatorFactory;
        $this->events = $events;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();

        $token = PasswordToken::findOrFail(Arr::get($input, 'passwordToken'));

        $password = Arr::get($input, 'password');

        try {
            // 待办事项：可能不应该使用用户验证器来处理这个，
            // 密码应该单独验证
            $this->validator->assertValid(compact('password'));

            $validator = $this->validatorFactory->make($input, ['password' => 'required|confirmed']);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        } catch (ValidationException $e) {
            $request->getAttribute('session')->put('errors', new MessageBag($e->errors()));

            // @待办事项：必须返回一个422状态码，查看可渲染的异常
            return new RedirectResponse($this->url->to('site')->route('resetPassword', ['token' => $token->token]));
        }

        $token->user->changePassword($password);
        $token->user->save();

        $this->dispatchEventsFor($token->user);

        $session = $request->getAttribute('session');
        $accessToken = SessionAccessToken::generate($token->user->id);
        $this->authenticator->logIn($session, $accessToken);

        return new RedirectResponse($this->url->to('site')->base());
    }
}
