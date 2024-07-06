<?php

namespace Forumkit\User\Command;

use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\User\EmailToken;
use Forumkit\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class ConfirmEmailHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Forumkit\User\UserRepository
     */
    protected $users;

    /**
     * @param \Forumkit\User\UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param ConfirmEmail $command
     * @return \Forumkit\User\User
     */
    public function handle(ConfirmEmail $command)
    {
        /** @var EmailToken $token */
        $token = EmailToken::validOrFail($command->token);

        $user = $token->user;
        $user->changeEmail($token->email);

        $user->activate();

        $user->save();
        $this->dispatchEventsFor($user);

        // 删除用户的 *all* 令牌，以防先发送其他令牌
        $user->emailTokens()->delete();

        return $user;
    }
}
