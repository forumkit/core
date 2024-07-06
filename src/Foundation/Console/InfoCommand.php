<?php

namespace Forumkit\Foundation\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Extension\ExtensionManager;
use Forumkit\Foundation\Application;
use Forumkit\Foundation\ApplicationInfoProvider;
use Forumkit\Foundation\Config;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class InfoCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var ApplicationInfoProvider
     */
    private $appInfo;

    public function __construct(
        ExtensionManager $extensions,
        Config $config,
        SettingsRepositoryInterface $settings,
        ConnectionInterface $db,
        ApplicationInfoProvider $appInfo
    ) {
        $this->extensions = $extensions;
        $this->config = $config;
        $this->settings = $settings;
        $this->db = $db;
        $this->appInfo = $appInfo;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription("收集有关 Forumkit 核心和已安装扩展的信息");
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $coreVersion = $this->findPackageVersion(__DIR__.'/../../../', Application::VERSION);
        $this->output->writeln("<info>Forumkit 核心版本：</info> $coreVersion");

        $this->output->writeln('<info>PHP 版本：</info> '.$this->appInfo->identifyPHPVersion());
        $this->output->writeln('<info>MySQL 版本：</info> '.$this->appInfo->identifyDatabaseVersion());

        $phpExtensions = implode(', ', get_loaded_extensions());
        $this->output->writeln("<info>已加载的扩展：</info> $phpExtensions");

        $this->getExtensionTable()->render();

        $this->output->writeln('<info>基础URL：</info> '.$this->config->url());
        $this->output->writeln('<info>安装路径：</info> '.getcwd());
        $this->output->writeln('<info>队列驱动：</info> '.$this->appInfo->identifyQueueDriver());
        $this->output->writeln('<info>会话驱动：</info> '.$this->appInfo->identifySessionDriver());

        if ($this->appInfo->scheduledTasksRegistered()) {
            $this->output->writeln('<info>调度器状态：</info> '.$this->appInfo->getSchedulerStatus());
        }

        $this->output->writeln('<info>邮件驱动：</info> '.$this->settings->get('mail_driver', 'unknown'));
        $this->output->writeln('<info>调试模式：</info> '.($this->config->inDebugMode() ? '<error>ON</error>' : 'off'));

        if ($this->config->inDebugMode()) {
            $this->output->writeln('');
            $this->error(
                "别忘了关闭调试模式！它永远不应在生产系统中开启。"
            );
        }
    }

    private function getExtensionTable()
    {
        $table = (new Table($this->output))
            ->setHeaders([
                ['Forumkit 扩展'],
                ['ID', '版本', '提交']
            ])->setStyle(
                (new TableStyle)->setCellHeaderFormat('<info>%s</info>')
            );

        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            $table->addRow([
                $extension->getId(),
                $extension->getVersion(),
                $this->findPackageVersion($extension->getPath())
            ]);
        }

        return $table;
    }

    /**
     * 尝试检测包的准确版本。
     *
     * 如果包似乎是一个 Git 版本，我们使用命令行提取当前签出的提交。
     */
    private function findPackageVersion(string $path, string $fallback = null): ?string
    {
        if (file_exists("$path/.git")) {
            $cwd = getcwd();
            chdir($path);

            $output = [];
            $status = null;
            exec('git rev-parse HEAD 2>&1', $output, $status);

            chdir($cwd);

            if ($status == 0) {
                return isset($fallback) ? "$fallback ($output[0])" : $output[0];
            }
        }

        return $fallback;
    }
}
