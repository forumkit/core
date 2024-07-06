<?php

namespace Forumkit\Foundation;

use Illuminate\Support\Str;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Document;

class MaintenanceModeHandler implements RequestHandlerInterface
{
    const MESSAGE = '当前正在维护中。请稍后再试。';

    /**
     * 处理请求并返回响应。
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // 对于API请求的特殊处理：它们将获得适当的API响应
        if ($this->isApiRequest($request)) {
            return $this->apiResponse();
        }

        // 默认情况下，返回一个简单的文本消息。
        return new HtmlResponse(self::MESSAGE, 503);
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        return Str::contains(
            $request->getHeaderLine('Accept'),
            'application/vnd.api+json'
        );
    }

    private function apiResponse(): ResponseInterface
    {
        return new JsonResponse(
            (new Document)->setErrors([
                'status' => '503',
                'title' => self::MESSAGE
            ]),
            503,
            ['Content-Type' => 'application/vnd.api+json']
        );
    }
}
