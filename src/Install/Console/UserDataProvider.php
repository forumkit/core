<?php

namespace Forumkit\Install\Console;

use Forumkit\Install\AdminUser;
use Forumkit\Install\BaseUrl;
use Forumkit\Install\DatabaseConfig;
use Forumkit\Install\Installation;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class UserDataProvider implements DataProviderInterface
{
    protected $input;

    protected $output;

    protected $questionHelper;

    /** @var BaseUrl */
    protected $baseUrl;

    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $questionHelper;
    }

    public function configure(Installation $installation): Installation
    {
        return $installation
            ->debugMode(false)
            ->baseUrl($this->getBaseUrl())
            ->databaseConfig($this->getDatabaseConfiguration())
            ->adminUser($this->getAdminUser())
            ->settings($this->getSettings());
    }

    private function getDatabaseConfiguration(): DatabaseConfig
    {
        $host = $this->ask('数据库主机（必填）：');
        $port = 3306;

        if (Str::contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        return new DatabaseConfig(
            'mysql',
            $host,
            intval($port),
            $this->ask('数据库名（必填）：'),
            $this->ask('数据库用户（必填）：'),
            $this->secret('数据库密码：'),
            $this->ask('表前缀：')
        );
    }

    private function getBaseUrl(): BaseUrl
    {
        $baseUrl = $this->ask('基础URL（默认：http://forumkit.localhost）：', 'http://forumkit.localhost');

        return $this->baseUrl = BaseUrl::fromString($baseUrl);
    }

    private function getAdminUser(): AdminUser
    {
        return new AdminUser(
            $this->ask('管理员用户名（默认：admin）：', 'admin'),
            $this->askForAdminPassword(),
            $this->ask('管理员电子邮件地址（必填）：')
        );
    }

    private function askForAdminPassword()
    {
        while (true) {
            $password = $this->secret('管理员密码（至少8个字符）：');

            if (strlen($password) < 8) {
                $this->validationError('密码必须至少为8个字符。');
                continue;
            }

            $confirmation = $this->secret('管理员密码（确认）：');

            if ($password !== $confirmation) {
                $this->validationError('密码与其确认密码不匹配。');
                continue;
            }

            return $password;
        }
    }

    private function getSettings()
    {
        $title = $this->ask('网站标题：');

        return [
            'site_title' => $title,
            'mail_from' => $this->baseUrl->toEmail('noreply'),
            'welcome_title' => '欢迎来到 '.$title,
        ];
    }

    private function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function secret($question)
    {
        $question = new Question("<question>$question</question> ");

        $question->setHidden(true)->setHiddenFallback(true);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function validationError($message)
    {
        $this->output->writeln("<error>$message</error>");
        $this->output->writeln('请重试。');
    }
}
