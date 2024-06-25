<?php

namespace Forumkit\Database\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Foundation\Paths;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;

class GenerateDumpCommand extends AbstractCommand
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Connection $connection
     * @param Paths $paths
     */
    public function __construct(Connection $connection, Paths $paths)
    {
        $this->connection = $connection;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('schema:dump')
            ->setDescription('导出数据库架构');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $dumpPath = __DIR__.'/../../../migrations/install.dump';
        /** @var Connection&MySqlConnection */
        $connection = resolve('db.connection');

        $connection
            ->getSchemaState()
            ->withMigrationTable($connection->getTablePrefix().'migrations')
            ->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            })
            ->dump($connection, $dumpPath);

        // 我们需要移除任何数据迁移，因为这些不会在架构导出中被捕获，必须单独运行。
        $coreDataMigrations = [
            '2024_06_11_000000_seed_default_groups',                // 2024年6月11日，00:00:00的默认组种子数据迁移
            '2024_06_11_000100_seed_default_group_permissions',     // 2024年6月11日，00:01:00的默认组权限种子数据迁移
        ];

        $newDump = [];
        $dump = file($dumpPath);
        foreach ($dump as $line) {
            foreach ($coreDataMigrations as $excludeMigrationId) {
                if (strpos($line, $excludeMigrationId) !== false) {
                    continue 2;
                }
            }
            $newDump[] = $line;
        }

        file_put_contents($dumpPath, implode($newDump));
    }
}
