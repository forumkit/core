<?php

namespace Forumkit\Foundation\ErrorHandling;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface HttpFormatter
{
    /**
     * Create an HTTP Response to represent the error we are handling.
     *
     * This method receives the error that was caught by Forumkit's error handling
     * stack, along with the current HTTP request instance. It should return an
     * HTTP response that explains or represents what went wrong.
     *
     * @param HandledError $error
     * @param Request $request
     * @return Response
     */
    public function format(HandledError $error, Request $request): Response;
}
