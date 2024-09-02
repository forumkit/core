<?php

namespace Forumkit\Foundation\ErrorHandling;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Log caught exceptions to a PSR-3 logger instance.
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
