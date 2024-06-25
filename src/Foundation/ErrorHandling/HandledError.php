<?php

namespace Forumkit\Foundation\ErrorHandling;

use Throwable;

/**
 * Forumkit 的错误处理堆栈捕获/解释的错误。
 *
 * 最重要的是，这样的错误有一个“类型”（用于查找翻译错误消息和视图以呈现漂亮的 HTML 页面）和 用于呈现 HTTP 错误响应的关联 HTTP 状态代码。
 */
class HandledError
{
    private $error;
    private $type;
    private $statusCode;

    private $details = [];

    public static function unknown(Throwable $error)
    {
        return new static($error, 'unknown', 500);
    }

    public function __construct(Throwable $error, $type, $statusCode)
    {
        $this->error = $error;
        $this->type = $type;
        $this->statusCode = $statusCode;
    }

    public function withDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getException(): Throwable
    {
        return $this->error;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function shouldBeReported(): bool
    {
        return $this->type === 'unknown';
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function hasDetails(): bool
    {
        return ! empty($this->details);
    }
}
