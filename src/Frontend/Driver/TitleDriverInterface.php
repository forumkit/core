<?php

namespace Forumkit\Frontend\Driver;

use Forumkit\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface;

interface TitleDriverInterface
{
    public function makeTitle(Document $document, ServerRequestInterface $request, array $forumApiDocument): string;
}
