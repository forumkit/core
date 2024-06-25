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
     * Map exceptions to handled errors.
     *
     * This can map internal ({@see \Forumkit\Foundation\KnownError}) as well as
     * external exceptions (any classes inheriting from \Throwable) to instances
     * of {@see \Forumkit\Foundation\ErrorHandling\HandledError}.
     *
     * Even for unknown exceptions, a generic fallback will always be returned.
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
