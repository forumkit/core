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
            ->setDescription('Run all migrations down for an extension')
            ->addOption(
                'extension',
                null,
                InputOption::VALUE_REQUIRED,
                'The extension to reset migrations for.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $extensionName = $this->input->getOption('extension');

        if (! $extensionName) {
            $this->info('No extension specified. Please check command syntax.');

            return;
        }

        $extension = $this->manager->getExtension($extensionName);

        if (! $extension) {
            $this->info('Could not find extension '.$extensionName);

            return;
        }

        $this->info('Rolling back extension: '.$extensionName);

        $this->manager->getMigrator()->setOutput($this->output);
        $this->manager->migrateDown($extension);

        $this->info('DONE.');
    }
}
