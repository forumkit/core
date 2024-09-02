<?php

namespace Forumkit\User;

use Forumkit\User\Event\EmailChanged;
use Forumkit\User\Event\PasswordChanged;
use Illuminate\Contracts\Events\Dispatcher;

class TokensClearer
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([PasswordChanged::class, EmailChanged::class], [$this, 'clearPasswordTokens']);
        $events->listen(PasswordChanged::class, [$this, 'clearEmailTokens']);
    }

    /**
     * @param PasswordChanged|EmailChanged $event
     */
    public function clearPasswordTokens($event): void
    {
        $event->user->passwordTokens()->delete();
    }

    /**
     * @param PasswordChanged $event
     */
    public function clearEmailTokens($event): void
    {
        $event->user->emailTokens()->delete();
    }
}
