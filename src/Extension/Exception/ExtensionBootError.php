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

        parent::__construct("在启动扩展时发生错误： {$extension->getTitle()}.\n\n在应用类型为 $extenderClass 的扩展器时发生错误。", 0, $previous);
    }
}
