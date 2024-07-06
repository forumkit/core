<?php

namespace Forumkit\Foundation\ErrorHandling;

use Forumkit\Foundation\KnownError;
use Throwable;

/**
 * Forumkit 的已知错误类型的中央注册表。
 *
 * 它知道如何处理 Forumkit 内部和外部提出的错误，将它们映射到错误“类型”以及如何确定适当的HTTP状态他们的代码。
 */
class Registry
{
    private $statusMap;
    private $classMap;
    private $handlerMap;

    public function __construct(array $statusMap, array $classMap, array $handlerMap)
    {
        $this->statusMap = $statusMap;
        $this->classMap = $classMap;
        $this->handlerMap = $handlerMap;
    }

    /**
     * 将异常映射为已处理的错误。
     *
     * 这可以将内部异常 ({@see \Forumkit\Foundation\KnownError}) 以及
     * 外部异常（任何继承自 \Throwable 的类）映射为 {@see \Forumkit\Foundation\ErrorHandling\HandledError} 的实例。
     *
     * 即使对于未知的异常，也始终会返回一个通用的回退选项。
     *
     * @param Throwable $error
     * @return HandledError
     */
    public function handle(Throwable $error): HandledError
    {
        return $this->handleKnownTypes($error)
            ?? $this->handleCustomTypes($error)
            ?? HandledError::unknown($error);
    }

    private function handleKnownTypes(Throwable $error): ?HandledError
    {
        $errorType = null;

        if ($error instanceof KnownError) {
            $errorType = $error->getType();
        } else {
            $errorClass = get_class($error);
            if (isset($this->classMap[$errorClass])) {
                $errorType = $this->classMap[$errorClass];
            }
        }

        if ($errorType) {
            return new HandledError(
                $error,
                $errorType,
                $this->statusMap[$errorType] ?? 500
            );
        }

        return null;
    }

    private function handleCustomTypes(Throwable $error): ?HandledError
    {
        $errorClass = get_class($error);

        if (isset($this->handlerMap[$errorClass])) {
            $handler = new $this->handlerMap[$errorClass];

            return $handler->handle($error);
        }

        return null;
    }
}
