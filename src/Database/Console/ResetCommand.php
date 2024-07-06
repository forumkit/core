<?php

namespace Forumkit\Database\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Extension\ExtensionManager;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $manager;

    /**
     * @param ExtensionManager $manager
     */
    public function __construct(ExtensionManager $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('migrate:reset')
            ->setDescription('为扩展执行所有回滚迁移')
            ->addOption(
                'extension',
                null,
                InputOption::VALUE_REQUIRED,
                '要重置迁移的扩展。'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $extensionName = $this->input->getOption('extension');

        if (! $extensionName) {
            $this->info('未指定扩展。请检查命令语法。');

            return;
        }

        $extension = $this->manager->getExtension($extensionName);

        if (! $extension) {
            $this->info('找不到扩展 '.$extensionName);

            return;
        }

        $this->info('正在回滚扩展：'.$extensionName);

        $this->manager->getMigrator()->setOutput($this->output);
        $this->manager->migrateDown($extension);

        $this->info('完成。');
    }
}
