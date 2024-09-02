<?php

namespace Forumkit\Extension\Exception;

use Exception;
use Forumkit\Extension\Extension;
use Throwable;

class ExtensionBootError extends Exception
{
    public $extension;
    public $extender;

    public function __construct(Extension $extension, $extender, Throwable $previous = null)
    {
        $this->extension = $extension;
        $this->extender = $extender;

        $extenderClass = get_class($extender);

        parent::__construct("Experienced an error while booting extension: {$extension->getTitle()}.\n\nError occurred while applying an extender of type: $extenderClass.", 0, $previous);
    }
}
