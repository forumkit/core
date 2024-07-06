<?php

namespace Forumkit\Api\Controller;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractDeleteController implements RequestHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->delete($request);

        return new EmptyResponse(204);
    }

    /**
     * 删除资源。
     *
     * @param ServerRequestInterface $request
     */
    abstract protected function delete(ServerRequestInterface $request);
}
