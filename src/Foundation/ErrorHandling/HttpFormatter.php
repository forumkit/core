<?php

namespace Forumkit\Foundation\ErrorHandling;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface HttpFormatter
{
    /**
     * 创建一个 HTTP 响应来表示我们正在处理的错误。
     *
     * 此方法接收 Forumkit 的错误处理捕获的错误堆栈，以及当前的 HTTP 请求实例。它应该返回一个解释或表示问题所在的 HTTP 响应。
     *
     * @param HandledError $error
     * @param Request $request
     * @return Response
     */
    public function format(HandledError $error, Request $request): Response;
}
