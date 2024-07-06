<?php

namespace Forumkit\Api\Controller;

use Forumkit\Http\AccessToken;
use Forumkit\Http\RequestUtil;
use Forumkit\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;

class DeleteAccessTokenController extends AbstractDeleteController
{
    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $actor->assertRegistered();

        $token = AccessToken::query()->findOrFail($id);

        /** @var Session|null $session */
        $session = $request->getAttribute('session');

        // 当前会话只能通过注销来终止。
        if ($session && $token->token === $session->get('access_token')) {
            throw new PermissionDeniedException();
        }

        // 不要泄露令牌的存在。
        if ($actor->cannot('revoke', $token)) {
            throw new ModelNotFoundException();
        }

        $token->delete();

        return new EmptyResponse(204);
    }
}
