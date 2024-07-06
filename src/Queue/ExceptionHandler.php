<?php

namespace Forumkit\Queue;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionHandler implements ExceptionHandling
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 报告或记录一个异常
     *
     * @param  Throwable $e
     * @return void
     */
    public function report(Throwable $e)
    {
        $this->logger->error((string) $e);
    }

    /**
     * 将异常渲染为 HTTP 响应
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Throwable               $e
     * @return void
     */
    public function render($request, Throwable $e) /** @phpstan-ignore-line */
    {
        // TODO: 实现 render() 方法
    }

    /**
     * 将异常渲染到控制台
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  Throwable                                        $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        // TODO: 实现 renderForConsole() 方法
    }

    /**
     * 确定是否应该报告此异常
     *
     * @param  Throwable $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        return true;
    }
}
