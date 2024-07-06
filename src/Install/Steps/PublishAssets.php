<?php

namespace Forumkit\Install\Steps;

use Forumkit\Install\ReversibleStep;
use Illuminate\Filesystem\Filesystem;

class PublishAssets implements ReversibleStep
{
    /**
     * @var string
     */
    private $vendorPath;

    /**
     * @var string
     */
    private $assetPath;

    public function __construct($vendorPath, $assetPath)
    {
        $this->vendorPath = $vendorPath;
        $this->assetPath = $assetPath;
    }

    public function getMessage()
    {
        return '发布所有资源';
    }

    public function run()
    {
        // 使用Filesystem类的实例来复制目录
        // 从指定的vendor路径下的components/font-awesome/webfonts目录
        // 复制到目标路径
        (new Filesystem)->copyDirectory(
            "$this->vendorPath/components/font-awesome/webfonts",
            $this->targetPath()
        );
    }

    public function revert()
    {
        (new Filesystem)->deleteDirectory($this->targetPath());
    }

    private function targetPath()
    {
        return "$this->assetPath/fonts";
    }
}
