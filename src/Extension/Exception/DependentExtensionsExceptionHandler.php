<?php

namespace Forumkit\Extension\Exception;

use Forumkit\Extension\ExtensionManager;
use Forumkit\Foundation\ErrorHandling\HandledError;

class DependentExtensionsExceptionHandler
{
    public function handle(DependentExtensionsException $e): HandledError
    {
        return (new HandledError(
            $e,
            'dependent_extensions',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(DependentExtensionsException $e): array
    {
        return [
            [
                'extension' => $e->extension->getTitle(),
                'extensions' => ExtensionManager::pluckTitles($e->dependent_extensions),
            ]
        ];
    }
}
