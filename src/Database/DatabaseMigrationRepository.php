<?php

namespace Forumkit\Database;

use Illuminate\Database\ConnectionInterface;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * 要使用的数据库连接名称。
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * 迁移表的名称。
     *
     * @var string
     */
    protected $table;

    /**
     * 创建一个新的数据库迁移仓库实例。
     *
     * @param  ConnectionInterface $connection
     * @param  string $table
     */
    public function __construct(ConnectionInterface $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * 获取已运行的迁移。
     *
     * @param string $extension
     * @return array
     */
    public function getRan($extension = null)
    {
        return $this->table()
                ->where('extension', $extension)
                ->orderBy('migration', 'asc')
                ->pluck('migration')
                ->toArray();
    }

    /**
     * 记录已运行的迁移。
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function log($file, $extension = null)
    {
        $record = ['migration' => $file, 'extension' => $extension];

        $this->table()->insert($record);
    }

    /**
     * 从日志中删除一个迁移。
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function delete($file, $extension = null)
    {
        $query = $this->table()->where('migration', $file);

        if (is_null($extension)) {
            $query->whereNull('extension');
        } else {
            $query->where('extension', $extension);
        }

        $query->delete();
    }

    /**
     * 确定迁移仓库是否存在。
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->connection->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * 获取迁移表的查询构造器。
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection->table($this->table);
    }
}
