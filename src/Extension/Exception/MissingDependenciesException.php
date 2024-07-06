<?php

namespace Forumkit\Extension\Exception;

use Exception;
use Forumkit\Extension\Extension;
use Forumkit\Extension\ExtensionManager;

/**
 * 当有人尝试启用一个扩展，但该扩展所依赖的Forumkit扩展并未全部启用时，会抛出此异常
 */
class MissingDependenciesException extends Exception
{
    public $extension;
    public $missing_dependencies;

    /**
     * @param $extension: 我们尝试启用的扩展
     * @param $missing_dependencies: 此扩展所依赖的，但尚未启用的扩展数组
     */
    public function __construct(Extension $extension, array $missing_dependencies = null)
    {
        $this->extension = $extension;
        $this->missing_dependencies = $missing_dependencies;

        parent::__construct($extension->getTitle().' 无法启用，因为它依赖于： '.implode(', ', ExtensionManager::pluckTitles($missing_dependencies)));
    }
}
