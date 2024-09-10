<?php

namespace Forumkit\Http\Middleware;

use Forumkit\Foundation\ErrorHandling\HttpFormatter;
use Forumkit\Foundation\ErrorHandling\Registry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * 捕获在PSR-15中间件堆栈中抛出的异常，并安全地处理它们。
 *
 * 所有错误都将使用提供的格式化程序进行渲染。此外，
 * 未知错误将被传递给一个或多个
 * {@see \Forumkit\Foundation\ErrorHandling\Reporter} 实例。
 */
class HandleErrors implements Middleware
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var HttpFormatter
     */
    protected $formatter;

    /**
     * @var \Forumkit\Foundation\ErrorHandling\Reporter[]
     */
    protected $reporters;

    public function __construct(Registry $registry, HttpFormatter $formatter, iterable $reporters)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->reporters = $reporters;
    }

    /**
     * 捕获在后续中间件执行过程中发生的所有错误。
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            $error = $this->registry->handle($e);

            if ($error->shouldBeReported()) {
                foreach ($this->reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }

            return $this->formatter->format($error, $request);
        }
    }
}
