<?php

namespace Forumkit\Extension\Exception;

use Exception;
use Forumkit\Extension\Extension;
use Forumkit\Extension\ExtensionManager;

/**
 * 当有人尝试禁用一个被其他已启用扩展所依赖的扩展时，会抛出此异常。
 */
class DependentExtensionsException extends Exception
{
    public $extension;
    public $dependent_extensions;

    /**
     * @param $extension: 我们尝试禁用的扩展
     * @param $dependent_extensions: 依赖此扩展的已启用 Forumkit 扩展数组
     */
    public function __construct(Extension $extension, array $dependent_extensions)
    {
        $this->extension = $extension;
        $this->dependent_extensions = $dependent_extensions;

        parent::__construct($extension->getTitle().' 无法被禁用，因为它被以下扩展所依赖: '.implode(', ', ExtensionManager::pluckTitles($dependent_extensions)));
    }
}
