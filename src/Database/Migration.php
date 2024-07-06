<?php

namespace Forumkit\Database;

use Forumkit\Extend\Settings;
use Forumkit\Settings\DatabaseSettingsRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * 迁移工厂
 *
 * 为创建典型的迁移实现了一些方便的快捷方式。
 */
abstract class Migration
{
    /**
     * 创建一个表。
     */
    public static function createTable($name, callable $definition)
    {
        return [
            'up' => function (Builder $schema) use ($name, $definition) {
                $schema->create($name, function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            },
            'down' => function (Builder $schema) use ($name) {
                $schema->drop($name);
            }
        ];
    }

    /**
     * 如果表不存在，则创建一个表。
     */
    public static function createTableIfNotExists($name, callable $definition)
    {
        return [
            'up' => function (Builder $schema) use ($name, $definition) {
                if (! $schema->hasTable($name)) {
                    $schema->create($name, function (Blueprint $table) use ($definition) {
                        $definition($table);
                    });
                }
            },
            'down' => function (Builder $schema) use ($name) {
                $schema->dropIfExists($name);
            }
        ];
    }

    /**
     * 重命名一个表。
     */
    public static function renameTable($from, $to)
    {
        return [
            'up' => function (Builder $schema) use ($from, $to) {
                $schema->rename($from, $to);
            },
            'down' => function (Builder $schema) use ($from, $to) {
                $schema->rename($to, $from);
            }
        ];
    }

    /**
     * 向表中添加列。
     */
    public static function addColumns($tableName, array $columnDefinitions)
    {
        return [
            'up' => function (Builder $schema) use ($tableName, $columnDefinitions) {
                $schema->table($tableName, function (Blueprint $table) use ($columnDefinitions) {
                    foreach ($columnDefinitions as $columnName => $options) {
                        $type = array_shift($options);
                        $table->addColumn($type, $columnName, $options);
                    }
                });
            },
            'down' => function (Builder $schema) use ($tableName, $columnDefinitions) {
                $schema->table($tableName, function (Blueprint $table) use ($columnDefinitions) {
                    $table->dropColumn(array_keys($columnDefinitions));
                });
            }
        ];
    }

    /**
     * 从表中删除列。
     */
    public static function dropColumns($tableName, array $columnDefinitions)
    {
        $inverse = static::addColumns($tableName, $columnDefinitions);

        return [
            'up' => $inverse['down'],
            'down' => $inverse['up']
        ];
    }

    /**
     * 重命名列。
     */
    public static function renameColumn($tableName, $from, $to)
    {
        return static::renameColumns($tableName, [$from => $to]);
    }

    /**
     * 重命名多个列。
     */
    public static function renameColumns($tableName, array $columnNames)
    {
        return [
            'up' => function (Builder $schema) use ($tableName, $columnNames) {
                $schema->table($tableName, function (Blueprint $table) use ($columnNames) {
                    foreach ($columnNames as $from => $to) {
                        $table->renameColumn($from, $to);
                    }
                });
            },
            'down' => function (Builder $schema) use ($tableName, $columnNames) {
                $schema->table($tableName, function (Blueprint $table) use ($columnNames) {
                    foreach ($columnNames as $to => $from) {
                        $table->renameColumn($from, $to);
                    }
                });
            }
        ];
    }

    /**
     * 为配置值添加默认值。
     *
     * @deprecated 使用 Settings 扩展器的 `default` 方法来替代注册设置。
     * @see Settings::default()
     */
    public static function addSettings(array $defaults)
    {
        return [
            'up' => function (Builder $schema) use ($defaults) {
                $settings = new DatabaseSettingsRepository(
                    $schema->getConnection()
                );

                foreach ($defaults as $key => $value) {
                    $settings->set($key, $value);
                }
            },
            'down' => function (Builder $schema) use ($defaults) {
                $settings = new DatabaseSettingsRepository(
                    $schema->getConnection()
                );

                foreach (array_keys($defaults) as $key) {
                    $settings->delete($key);
                }
            }
        ];
    }

    /**
     * 添加默认权限。
     */
    public static function addPermissions(array $permissions)
    {
        $rows = [];

        foreach ($permissions as $permission => $groups) {
            foreach ((array) $groups as $group) {
                $rows[] = [
                    'group_id' => $group,
                    'permission' => $permission,
                ];
            }
        }

        return [
            'up' => function (Builder $schema) use ($rows) {
                $db = $schema->getConnection();

                foreach ($rows as $row) {
                    if ($db->table('group_permission')->where($row)->exists()) {
                        continue;
                    }

                    if ($db->table('groups')->where('id', $row['group_id'])->doesntExist()) {
                        continue;
                    }

                    $db->table('group_permission')->insert($row);
                }
            },

            'down' => function (Builder $schema) use ($rows) {
                $db = $schema->getConnection();

                foreach ($rows as $row) {
                    $db->table('group_permission')->where($row)->delete();
                }
            }
        ];
    }
}
