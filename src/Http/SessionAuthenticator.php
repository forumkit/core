<?php

namespace Forumkit\Http;

use Illuminate\Contracts\Session\Session;

class SessionAuthenticator
{
    /**
     * @param Session $session
     * @param AccessToken $token
     */
    public function logIn(Session $session, AccessToken $token)
    {
        $session->regenerate(true);
        $session->put('access_token', $token->token);
    }

    /**
     * @param Session $session
     */
    public function logOut(Session $session)
    {
        $token = AccessToken::findValid($session->get('access_token'));

        if ($token) {
            $token->delete();
        }

        $session->invalidate();
        $session->regenerateToken();
    }
}
