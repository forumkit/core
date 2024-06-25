<?php

namespace Forumkit\Foundation\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Extension\ExtensionManager;
use Forumkit\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;

class AssetsPublishCommand extends AbstractCommand
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Container $container
     * @param Paths $paths
     */
    public function __construct(Container $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('assets:publish')
            ->setDescription('发布核心和扩展的资产文件');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('正在发布核心资产文件...');

        $target = $this->container->make('filesystem')->disk('forumkit-assets');
        $local = new Filesystem();

        $pathPrefix = $this->paths->vendor.'/components/font-awesome/webfonts';
        $assetFiles = $local->allFiles($pathPrefix);

        foreach ($assetFiles as $fullPath) {
            $relPath = substr($fullPath, strlen($pathPrefix));
            $target->put("fonts/$relPath", $local->get($fullPath));
        }

        $this->info('正在发布扩展资产文件...');

        $extensions = $this->container->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasAssets()) {
                $this->info('正在为扩展发布：'.$name);
                $extension->copyAssetsTo($target);
            }
        }
    }
}
