<?php

namespace Forumkit\Install\Console;

use Exception;
use Forumkit\Install\AdminUser;
use Forumkit\Install\BaseUrl;
use Forumkit\Install\DatabaseConfig;
use Forumkit\Install\Installation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class FileDataProvider implements DataProviderInterface
{
    protected $debug = false;
    protected $baseUrl = null;
    protected $databaseConfiguration = [];
    protected $adminUser = [];
    protected $settings = [];
    protected $extensions = [];

    public function __construct(InputInterface $input)
    {
        // 获取配置文件路径
        $configurationFile = $input->getOption('file');

        // 在解析内容之前检查文件是否存在
        if (file_exists($configurationFile)) {
            $configurationFileContents = file_get_contents($configurationFile);
            // 尝试解析JSON
            if (($json = json_decode($configurationFileContents, true)) !== null) {
                // 如果有效则使用JSON
                $configuration = $json;
            } else {
                // 否则使用YAML
                $configuration = Yaml::parse($configurationFileContents);
            }

            // 定义配置变量
            $this->debug = $configuration['debug'] ?? false;
            $this->baseUrl = $configuration['baseUrl'] ?? 'http://forumkit.localhost';
            $this->databaseConfiguration = $configuration['databaseConfiguration'] ?? [];
            $this->adminUser = $configuration['adminUser'] ?? [];
            $this->settings = $configuration['settings'] ?? [];
            $this->extensions = explode(',', $configuration['extensions'] ?? '');
        } else {
            throw new Exception('配置文件不存在。');
        }
    }

    public function configure(Installation $installation): Installation
    {
        return $installation
            ->debugMode($this->debug)
            ->baseUrl(BaseUrl::fromString($this->baseUrl))
            ->databaseConfig($this->getDatabaseConfiguration())
            ->adminUser($this->getAdminUser())
            ->settings($this->settings)
            ->extensions($this->extensions);
    }

    private function getDatabaseConfiguration(): DatabaseConfig
    {
        return new DatabaseConfig(
            $this->databaseConfiguration['driver'] ?? 'mysql',
            $this->databaseConfiguration['host'] ?? 'localhost',
            $this->databaseConfiguration['port'] ?? 3306,
            $this->databaseConfiguration['database'] ?? 'forumkit',
            $this->databaseConfiguration['username'] ?? 'root',
            $this->databaseConfiguration['password'] ?? '',
            $this->databaseConfiguration['prefix'] ?? ''
        );
    }

    private function getAdminUser(): AdminUser
    {
        return new AdminUser(
            $this->adminUser['username'] ?? 'admin',
            $this->adminUser['password'] ?? 'password',
            $this->adminUser['email'] ?? 'admin@example.com'
        );
    }
}
