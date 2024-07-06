<?php

namespace Forumkit\Foundation\ErrorHandling;

use Franzl\Middleware\Whoops\WhoopsRunner;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 使用 Whoops 错误处理程序进行调试时的错误处理。
 *
 * 对于所有已知的错误类型，都会返回正确的状态码。
 * 此外，还会进行内容协商，以便在各种环境中（如 HTML 前端或 API 后端）返回适当的响应。
 *
 * 仅在调试模式下使用（因为 Whoops 可能会暴露敏感数据）。
 */
class WhoopsFormatter implements HttpFormatter
{
    public function format(HandledError $error, Request $request): Response
    {
        return WhoopsRunner::handle($error->getException(), $request)
            ->withStatus($error->getStatusCode());
    }
}
