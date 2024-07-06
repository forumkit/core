<?php

namespace Forumkit\Database;

use Forumkit\Database\Exception\MigrationKeyMissing;
use Forumkit\Extension\Extension;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class Migrator
{
    /**
     * 迁移仓库的实现。
     *
     * @var \Forumkit\Database\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * 文件系统实例。
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * 输出接口实现。
     *
     * @var OutputInterface|null
     */
    protected $output;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * 创建一个新的迁移器实例。
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        ConnectionInterface $connection,
        Filesystem $files
    ) {
        $this->files = $files;
        $this->repository = $repository;

        if (! ($connection instanceof MySqlConnection)) {
            throw new InvalidArgumentException('仅支持MySQL连接');
        }

        $this->connection = $connection;

        // 针对 https://github.com/laravel/framework/issues/1186 的解决方案
        $connection->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * 在给定路径下运行未完成的迁移。
     *
     * @param  string    $path
     * @param  Extension|null $extension
     * @return void
     */
    public function run($path, Extension $extension = null)
    {
        $files = $this->getMigrationFiles($path);

        $ran = $this->repository->getRan($extension ? $extension->getId() : null);

        $migrations = array_diff($files, $ran);

        $this->runMigrationList($path, $migrations, $extension);
    }

    /**
     * 运行一系列迁移。
     *
     * @param  string    $path
     * @param  array     $migrations
     * @param  Extension|null $extension
     * @return void
     */
    public function runMigrationList($path, $migrations, Extension $extension = null)
    {
        // 首先，我们确保有要运行的迁移。如果没有，我们会通知开发者，让他们知道所有的迁移都已经针对这个数据库系统执行过了。
        if (count($migrations) == 0) {
            $this->note('<info>没有什么可迁移的。</info>');

            return;
        }

        // 一旦我们有了迁移数组，我们将遍历它们并运行  "up"  “向上” 的迁移，以便对数据库进行更改。然后，我们将记录迁移已运行，以避免下次执行时重复执行。
        foreach ($migrations as $file) {
            $this->runUp($path, $file, $extension);
        }
    }

    /**
     * 运行迁移实例的 "up"  “向上” 操作。
     *
     * @param  string    $path
     * @param  string    $file
     * @param  Extension|null $extension
     * @return void
     */
    protected function runUp($path, $file, Extension $extension = null)
    {
        $this->resolveAndRunClosureMigration($path, $file);

        // 一旦我们运行了一个迁移类，我们将记录它已经被运行，这样下次我们进行迁移时就不会再尝试运行它。迁移仓库用于保持迁移的顺序。
        $this->repository->log($file, $extension ? $extension->getId() : null);

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * 回滚所有当前已应用的迁移。
     *
     * @param  string    $path
     * @param  Extension|null $extension
     * @return int
     */
    public function reset($path, Extension $extension = null)
    {
        $migrations = array_reverse($this->repository->getRan(
            $extension ? $extension->getId() : null
        ));

        $count = count($migrations);

        if ($count === 0) {
            $this->note('<info>没有需要回滚的迁移。</info>');
        } else {
            foreach ($migrations as $migration) {
                $this->runDown($path, $migration, $extension);
            }
        }

        return $count;
    }

    /**
     * 运行一个 "down" “向下”的迁移实例。
     *
     * @param  string    $path
     * @param  string    $file
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    protected function runDown($path, $file, Extension $extension = null)
    {
        $this->resolveAndRunClosureMigration($path, $file, 'down');

        // 一旦我们成功运行了迁移的 "down" “向下”操作，我们将从迁移仓库中删除它，这样应用程序将认为它没有被运行过，并且可以被后续的任何操作触发。
        $this->repository->delete($file, $extension ? $extension->getId() : null);

        $this->note("<info>已回滚：</info> $file");
    }

    /**
     * 根据迁移方向运行闭包迁移。
     *
     * @param        $migration
     * @param string $direction
     * @throws MigrationKeyMissing
     */
    protected function runClosureMigration($migration, $direction = 'up')
    {
        if (is_array($migration) && array_key_exists($direction, $migration)) {
            call_user_func($migration[$direction], $this->connection->getSchemaBuilder());
        } else {
            throw new MigrationKeyMissing($direction);
        }
    }

    /**
     * 解析并运行迁移，如果需要的话，将文件名分配给异常。
     *
     * @param string $path
     * @param string $file
     * @param string $direction
     * @throws MigrationKeyMissing
     */
    protected function resolveAndRunClosureMigration(string $path, string $file, string $direction = 'up')
    {
        $migration = $this->resolve($path, $file);

        try {
            $this->runClosureMigration($migration, $direction);
        } catch (MigrationKeyMissing $exception) {
            throw $exception->withFile("$path/$file.php");
        }
    }

    /**
     * 获取给定路径下的所有迁移文件。
     *
     * @param  string $path
     * @return array
     */
    public function getMigrationFiles($path)
    {
        $files = $this->files->glob($path.'/*_*.php');

        if ($files === false) {
            return [];
        }

        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

        // 对格式化后的文件名进行排序。因为文件名都以时间戳开头，这可以确保它们按实际创建顺序排序。
        sort($files);

        return $files;
    }

    /**
     * 从文件解析迁移实例。
     *
     * @param  string $path
     * @param  string $file
     * @return array
     */
    public function resolve($path, $file)
    {
        $migration = "$path/$file.php";

        if ($this->files->exists($migration)) {
            return $this->files->getRequire($migration);
        }

        return [];
    }

    /**
     * 从模式转储初始化 Forumkit 数据库。
     *
     * @param string $path 转储目录
     */
    public function installFromSchema(string $path)
    {
        // 构造模式转储文件的完整路径。
        $schemaPath = "$path/install.dump";

        // 记录开始时间。
        $startTime = microtime(true);

        // 读取模式转储文件的内容。
        $dump = file_get_contents($schemaPath);

        // 禁用外键约束，以便在导入时不会因外键冲突而失败。
        $this->connection->getSchemaBuilder()->disableForeignKeyConstraints();

        // 按分号分割SQL语句，并遍历执行每个语句。
        foreach (explode(';', $dump) as $statement) {
            $statement = trim($statement);

            // 跳过空行和注释行。
            if (empty($statement) || substr($statement, 0, 2) === '/*') {
                continue;
            }

            // 替换SQL语句中的'db_prefix_'为当前连接使用的表前缀。
            $statement = str_replace(
                'db_prefix_',
                $this->connection->getTablePrefix(),
                $statement
            );
            // 执行SQL语句。
            $this->connection->statement($statement);
        }

        // 重新启用外键约束。
        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

        // 计算并输出执行时间。
        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);
        $this->note('<info>已加载存储的数据库模式。</info> ('.$runTime.'ms)');
    }

    /**
     * 设置应由控制台使用的输出实现。
     *
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * 向控制台的输出写入一条信息。
     *
     * @param string $message
     * @return void
     */
    protected function note($message)
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }

    /**
     * 确定迁移仓库是否存在。
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }
}
