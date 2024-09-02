<?php

namespace Forumkit\Forum\Content;

use Forumkit\Frontend\Document;
use Forumkit\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssertRegistered
{
    public function __invoke(Document $document, Request $request)
    {
        RequestUtil::getActor($request)->assertRegistered();
    }
}
