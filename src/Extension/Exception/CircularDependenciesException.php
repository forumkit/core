<?php

namespace Forumkit\Extension\Exception;

use Exception;
use Forumkit\Extension\ExtensionManager;

class CircularDependenciesException extends Exception
{
    public $circular_dependencies;

    public function __construct(array $circularDependencies)
    {
        $this->circular_dependencies = $circularDependencies;

        parent::__construct('检测到循环依赖: '.implode(', ', ExtensionManager::pluckTitles($circularDependencies)).' - 正在中止。请通过禁用导致循环依赖的扩展来解决此问题。');
    }
}
