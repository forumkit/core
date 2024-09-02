<?php

namespace Forumkit\Api\Controller;

use Forumkit\Foundation\Console\AssetsPublishCommand;
use Forumkit\Foundation\Console\CacheClearCommand;
use Forumkit\Foundation\IOException;
use Forumkit\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCacheController extends AbstractDeleteController
{
    /**
     * @var CacheClearCommand
     */
    protected $command;

    /**
     * @var AssetsPublishCommand
     */
    protected $assetsPublishCommand;

    /**
     * @param CacheClearCommand $command
     */
    public function __construct(CacheClearCommand $command, AssetsPublishCommand $assetsPublishCommand)
    {
        $this->command = $command;
        $this->assetsPublishCommand = $assetsPublishCommand;
    }

    /**
     * {@inheritdoc}
     * @throws IOException|\Forumkit\User\Exception\PermissionDeniedException
     */
    protected function delete(ServerRequestInterface $request)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $exitCode = $this->command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new IOException();
        }

        $exitCode = $this->assetsPublishCommand->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new IOException();
        }

        return new EmptyResponse(204);
    }
}
