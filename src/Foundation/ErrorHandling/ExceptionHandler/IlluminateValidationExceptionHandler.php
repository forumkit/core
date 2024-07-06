<?php

namespace Forumkit\Foundation\ErrorHandling\ExceptionHandler;

use Forumkit\Foundation\ErrorHandling\HandledError;
use Illuminate\Validation\ValidationException;

class IlluminateValidationExceptionHandler
{
    public function handle(ValidationException $e): HandledError
    {
        return (new HandledError(
            $e,
            'validation_error',
            422
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(ValidationException $e): array
    {
        $errors = $e->errors();

        return array_map(function ($field, $messages) {
            return [
                'detail' => implode("\n", $messages),
                'source' => ['pointer' => "/data/attributes/$field"]
            ];
        }, array_keys($errors), $errors);
    }
}
