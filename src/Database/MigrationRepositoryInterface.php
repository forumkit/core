<?php

namespace Forumkit\Database;

interface MigrationRepositoryInterface
{
    /**
     * 获取给定扩展的已运行迁移。
     *
     * @param string $extension
     * @return array
     */
    public function getRan($extension = null);

    /**
     * 记录已运行的迁移。
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function log($file, $extension = null);

    /**
     * 从日志中删除迁移。
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function delete($file, $extension = null);

    /**
     * 确定迁移存储库是否存在。
     *
     * @return bool
     */
    public function repositoryExists();
}
