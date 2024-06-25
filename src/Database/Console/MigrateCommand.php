<?php

namespace Forumkit\Database\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Database\Migrator;
use Forumkit\Extension\ExtensionManager;
use Forumkit\Foundation\Application;
use Forumkit\Foundation\Paths;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;

class MigrateCommand extends AbstractCommand
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
            ->setName('migrate')
            ->setDescription('运行待处理的迁移');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('正在迁移 Forumkit...');

        $this->upgrade();

        $this->info('完成。');
    }

    public function upgrade()
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        $migrator = $this->container->make(Migrator::class);
        $migrator->setOutput($this->output);

        // 运行位于指定目录下的迁移文件
        $migrator->run(__DIR__.'/../../../migrations');

        $extensions = $this->container->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        // 遍历所有已启用的扩展
        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            // 如果扩展包含迁移文件
            if ($extension->hasMigrations()) {
                // 输出正在迁移的扩展名
                $this->info('迁移扩展：'.$name);

                // 对该扩展执行迁移操作
                $extensions->migrate($extension);
            }
        }

        $this->container->make(SettingsRepositoryInterface::class)->set('version', Application::VERSION);
    }
}
