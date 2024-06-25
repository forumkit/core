<?php

namespace Forumkit\Foundation\ErrorHandling\ExceptionHandler;

use Forumkit\Foundation\ErrorHandling\HandledError;
use Forumkit\Foundation\ValidationException;

class ValidationExceptionHandler
{
    public function handle(ValidationException $e)
    {
        return (new HandledError(
            $e,
            'validation_error',
            422
        ))->withDetails(array_merge(
            $this->buildDetails($e->getAttributes(), '/data/attributes'),
            $this->buildDetails($e->getRelationships(), '/data/relationships')
        ));
    }

    private function buildDetails(array $messages, $pointer): array
    {
        return array_map(function ($path, $detail) use ($pointer) {
            return [
                'detail' => $detail,
                'source' => ['pointer' => $pointer.'/'.$path]
            ];
        }, array_keys($messages), $messages);
    }
}
