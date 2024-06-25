<?php

namespace Forumkit\Extension\Exception;

use Exception;
use Forumkit\Extension\Extension;
use Forumkit\Extension\ExtensionManager;

/**
 * This exception is thrown when someone attempts to disable an extension
 * that other enabled extensions depend on.
 */
class DependentExtensionsException extends Exception
{
    public $extension;
    public $dependent_extensions;

    /**
     * @param $extension: The extension we are attempting to disable.
     * @param $dependent_extensions: Enabled Forumkit extensions that depend on this extension.
     */
    public function __construct(Extension $extension, array $dependent_extensions)
    {
        $this->extension = $extension;
        $this->dependent_extensions = $dependent_extensions;

        parent::__construct($extension->getTitle().' could not be disabled, because it is a dependency of: '.implode(', ', ExtensionManager::pluckTitles($dependent_extensions)));
    }
}
