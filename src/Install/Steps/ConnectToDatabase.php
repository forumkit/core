<?php

namespace Forumkit\Install\Steps;

use Forumkit\Install\DatabaseConfig;
use Forumkit\Install\Step;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Str;
use RangeException;

class ConnectToDatabase implements Step
{
    private $dbConfig;
    private $store;

    public function __construct(DatabaseConfig $dbConfig, callable $store)
    {
        $this->dbConfig = $dbConfig;
        $this->store = $store;
    }

    public function getMessage()
    {
        return '连接到数据库';
    }

    public function run()
    {
        // 将数据库配置转换为数组
        $config = $this->dbConfig->toArray();
        // 使用 MySqlConnector 类连接到数据库
        $pdo = (new MySqlConnector)->connect($config);

        // 查询数据库版本
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();

        // 检查版本是否是 MariaDB
        if (Str::contains($version, 'MariaDB')) {
            // 如果是 MariaDB，则检查版本是否低于 10.0.5
            if (version_compare($version, '10.0.5', '<')) {
                // 如果版本过低，则抛出异常
                throw new RangeException('MariaDB 版本过低。您至少需要 MariaDB 10.0.5');
            }
        } else {
            // 如果不是 MariaDB，则默认检查是否为 MySQL 并检查版本是否低于 5.6.0
            if (version_compare($version, '5.6.0', '<')) {
                // 如果版本过低，则抛出异常
                throw new RangeException('MySQL 版本过低。您至少需要 MySQL 5.6.');
            }
        }

        ($this->store)(
            new MySqlConnection(
                $pdo,
                $config['database'],
                $config['prefix'],
                $config
            )
        );
    }
}
