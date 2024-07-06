<?php

namespace Forumkit\Install;

use Forumkit\Foundation\AppInterface;
use Forumkit\Foundation\ErrorHandling\Registry;
use Forumkit\Foundation\ErrorHandling\Reporter;
use Forumkit\Foundation\ErrorHandling\WhoopsFormatter;
use Forumkit\Http\Middleware as HttpMiddleware;
use Forumkit\Install\Console\InstallCommand;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class Installer implements AppInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new HttpMiddleware\HandleErrors(
            $this->container->make(Registry::class),
            $this->container->make(WhoopsFormatter::class),
            $this->container->tagged(Reporter::class)
        ));
        $pipe->pipe($this->container->make(HttpMiddleware\StartSession::class));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('forumkit.install.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return [
            new InstallCommand(
                $this->container->make(Installation::class)
            ),
        ];
    }
}
