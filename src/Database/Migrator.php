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
     * 迁移存储库实现
     *
     * @var \Forumkit\Database\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * 输出接口实现
     *
     * @var OutputInterface|null
     */
    protected $output;

    /**
     * 数据库连接实例
     * 
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * 创建一个新的迁移器实例
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        ConnectionInterface $connection,
        Filesystem $files
    ) {
        $this->files = $files;
        $this->repository = $repository;

        // 强制仅支持MySQL连接
        if (! ($connection instanceof MySqlConnection)) {
            throw new InvalidArgumentException('Only MySQL connections are supported');
        }

        $this->connection = $connection;

        // 修正Laravel框架的一个问题
        $connection->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * 在给定路径下运行待处理的迁移
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
     * 运行一个迁移列表
     *
     * @param  string    $path
     * @param  array     $migrations
     * @param  Extension|null $extension
     * @return void
     */
    public function runMigrationList($path, $migrations, Extension $extension = null)
    {
        // 首先，我们确保有迁移需要运行。如果没有，则通知开发者所有迁移都已运行。
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // 遍历迁移数组，并运行它们，将更改应用到数据库中。然后记录迁移的运行情况，避免重复执行。
        foreach ($migrations as $file) {
            $this->runUp($path, $file, $extension);
        }
    }

    /**
     * 运行一个迁移实例的 向上 "up" 操作
     *
     * @param  string    $path
     * @param  string    $file
     * @param  Extension|null $extension
     * @return void
     */
    protected function runUp($path, $file, Extension $extension = null)
    {
        $this->resolveAndRunClosureMigration($path, $file);

        // 运行迁移类后，我们将记录它已在此存储库中运行，以便下次在应用程序中进行迁移时，我们就不会尝试运行它。
        // 迁移存储库保持迁移顺序。
        $this->repository->log($file, $extension ? $extension->getId() : null);

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * 回滚所有已应用的迁移
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
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            foreach ($migrations as $migration) {
                $this->runDown($path, $migration, $extension);
            }
        }

        return $count;
    }

    /**
     * 运行一个迁移实例的 向下 "down" 操作
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

        // 一旦我们成功 关闭  "down" 了迁移，我们会将其从迁移存储库中删除，
        // 这样它就会被视为没有被应用程序运行，然后就可以被任何后续操作触发。
        $this->repository->delete($file, $extension ? $extension->getId() : null);

        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * 根据迁移方向运行闭包迁移
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
     * 解析并运行迁移，如果需要，将文件名附加到异常中
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
     * 获取给定路径下的所有迁移文件
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

        // 对文件名进行排序，因为它们都以时间戳开头，所以这将按照它们被创建的顺序进行排序。
        sort($files);

        return $files;
    }

    /**
     * 从文件中解析迁移实例
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
     * 从架构转储文件初始化Forumkit数据库
     *
     * @param string $path 包含转储文件的目录路径
     */
    public function installFromSchema(string $path)
    {
        $schemaPath = "$path/install.dump";

        $startTime = microtime(true);

        $dump = file_get_contents($schemaPath);

        $this->connection->getSchemaBuilder()->disableForeignKeyConstraints();

        foreach (explode(';', $dump) as $statement) {
            $statement = trim($statement);

            if (empty($statement) || substr($statement, 0, 2) === '/*') {
                continue;
            }

            $statement = str_replace(
                'db_prefix_',
                $this->connection->getTablePrefix(),
                $statement
            );
            $this->connection->statement($statement);
        }

        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);
        $this->note('<info>Loaded stored database schema.</info> ('.$runTime.'ms)');
    }

    /**
     * 设置控制台应使用的输出实现
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
     * 向控制台输出一条信息
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
     * 判断迁移仓库是否存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }
}
