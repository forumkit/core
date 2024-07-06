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
 * 不要与CreateAccessTokenController混淆，
 * 这个控制器用于通过凭据验证用户身份，
 * 并返回一个系统生成的会话类型访问令牌。
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

        // 即使在令牌之后从未被使用，也先更新它，以记录令牌创建者的IP/代理信息
        $token->touch($request);

        return new JsonResponse([
            'token' => $token->token,
            'userId' => $user->id
        ]);
    }
}
