<?php

namespace Forumkit\Extension\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Extension\ExtensionManager;

class ToggleExtensionCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensionManager;

    public function __construct(ExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extension:enable')
            ->setAliases(['extension:disable'])
            ->setDescription('启用或禁用一个扩展')
            ->addArgument('extension-id', null, '要启用或禁用的扩展的ID。');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $name = $this->input->getArgument('extension-id');
        $enabling = $this->input->getFirstArgument() === 'extension:enable';

        if ($this->extensionManager->getExtension($name) === null) {
            $this->error("不存在ID为 '$name' 的扩展。");

            return;
        }

        switch ($enabling) {
            case true:
                if ($this->extensionManager->isEnabled($name)) {
                    $this->info("'$name' 扩展已经启用。");

                    return;
                } else {
                    $this->info("正在启用 '$name' 扩展...");
                    $this->extensionManager->enable($name);
                }
                break;
            case false:
                if (! $this->extensionManager->isEnabled($name)) {
                    $this->info("'$name' 扩展已经禁用。");

                    return;
                } else {
                    $this->info("正在禁用 '$name' 扩展...");
                    $this->extensionManager->disable($name);
                }
                break;
        }
    }
}
