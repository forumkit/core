<?php

namespace Forumkit\Foundation\ErrorHandling;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 日志捕获到 PSR-3 记录器实例的异常。
 */
class LogReporter implements Reporter
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function report(Throwable $error)
    {
        $this->logger->error($error);
    }
}
