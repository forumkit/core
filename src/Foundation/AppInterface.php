<?php

namespace Forumkit\Foundation;

interface AppInterface
{
    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler();

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands();
}
