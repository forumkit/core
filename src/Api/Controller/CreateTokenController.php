<?php

namespace Forumkit\Api\Controller;

use Forumkit\Http\RememberAccessToken;
use Forumkit\Http\SessionAccessToken;
use Forumkit\User\Exception\NotAuthenticatedException;
use Forumkit\User\UserRepository;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 注意不要与 CreateAccessTokenController 混淆，
 * 此控制器用于通过用户凭证进行身份验证，
 * 并返回一个系统生成的会话式访问令牌。
 */
class CreateTokenController implements RequestHandlerInterface
{
    /**
     * @var \Forumkit\User\UserRepository
     */
    protected $users;

    /**
     * @var BusDispatcher
     */
    protected $bus;

    /**
     * @var EventDispatcher
     */
    protected $events;

    /**
     * @param UserRepository $users
     * @param BusDispatcher $bus
     * @param EventDispatcher $events
     */
    public function __construct(UserRepository $users, BusDispatcher $bus, EventDispatcher $events)
    {
        $this->users = $users;
        $this->bus = $bus;
        $this->events = $events;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        $identification = Arr::get($body, 'identification');
        $password = Arr::get($body, 'password');

        $user = $this->users->findByIdentification($identification);

        if (! $user || ! $user->checkPassword($password)) {
            throw new NotAuthenticatedException;
        }

        if (Arr::get($body, 'remember')) {
            $token = RememberAccessToken::generate($user->id);
        } else {
            $token = SessionAccessToken::generate($user->id);
        }

        // 我们在这里进行第一次更新以记录令牌创建者的IP/代理，即使此后从未使用过令牌
        $token->touch($request);

        return new JsonResponse([
            'token' => $token->token,
            'userId' => $user->id
        ]);
    }
}
