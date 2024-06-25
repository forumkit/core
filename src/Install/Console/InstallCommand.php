<?php

namespace Forumkit\Install\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Install\Installation;
use Forumkit\Install\Pipeline;
use Forumkit\Install\Step;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends AbstractCommand
{
    /**
     * @var Installation
     */
    protected $installation;

    /**
     * @var DataProviderInterface
     */
    protected $dataSource;

    /**
     * @param Installation $installation
     */
    public function __construct(Installation $installation)
    {
        $this->installation = $installation;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription("运行 Forumkit 的安装迁移和种子")
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                '使用 JSON 或 YAML 格式的外部配置文件'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                '设置将配置文件写入的路径'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->init();

        $problems = $this->installation->prerequisites()->problems();

        if ($problems->isEmpty()) {
            $this->info('正在安装 Forumkit...');

            $this->install();

            $this->info('DONE.');
        } else {
            $this->showProblems($problems);

            return 1;
        }
    }

    protected function init()
    {
        if ($this->input->getOption('file')) {
            $this->dataSource = new FileDataProvider($this->input);
        } else {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelperSet()->get('question');
            $this->dataSource = new UserDataProvider($this->input, $this->output, $questionHelper);
        }
    }

    protected function install()
    {
        $pipeline = $this->dataSource->configure(
            $this->installation->configPath($this->input->getOption('config'))
        )->build();

        $this->runPipeline($pipeline);
    }

    private function runPipeline(Pipeline $pipeline)
    {
        $pipeline
            ->on('start', function (Step $step) {
                $this->output->write($step->getMessage().'...');
            })->on('end', function () {
                $this->output->write("<info>done</info>\n");
            })->on('fail', function () {
                $this->output->write("<error>failed</error>\n");
                $this->output->writeln('Rolling back...');
            })->on('rollback', function (Step $step) {
                $this->output->writeln($step->getMessage().' (rollback)');
            })
            ->run();
    }

    protected function showProblems($problems)
    {
        $this->output->writeln(
            '<error>请先解决以下问题，然后我们才能继续安装。</error>'
        );

        foreach ($problems as $problem) {
            $this->info($problem['message']);

            if (isset($problem['detail'])) {
                $this->output->writeln('<comment>'.$problem['detail'].'</comment>');
            }
        }
    }
}
