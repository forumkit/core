<?php

namespace Forumkit\Install;

use Illuminate\Contracts\Support\Arrayable;

class DatabaseConfig implements Arrayable
{
    private $driver;
    private $host;
    private $port;
    private $database;
    private $username;
    private $password;
    private $prefix;

    public function __construct($driver, $host, $port, $database, $username, $password, $prefix)
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->prefix = $prefix;

        $this->validate();
    }

    public function toArray()
    {
        return [
            'driver'    => $this->driver,
            'host'      => $this->host,
            'port'      => $this->port,
            'database'  => $this->database,
            'username'  => $this->username,
            'password'  => $this->password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $this->prefix,
            'strict'    => false,
            'engine'    => 'InnoDB',
            'prefix_indexes' => true
        ];
    }

    private function validate()
    {
        if (empty($this->driver)) {
            throw new ValidationFailed('请指定一个数据库驱动程序。');
        }

        if ($this->driver !== 'mysql') {
            throw new ValidationFailed('当前仅支持MySQL/MariaDB。');
        }

        if (empty($this->host)) {
            throw new ValidationFailed('请指定您的数据库服务器的主机名。');
        }

        if (! is_int($this->port) || $this->port < 1 || $this->port > 65535) {
            throw new ValidationFailed('请提供一个介于1到65535之间的有效端口号。');
        }

        if (empty($this->database)) {
            throw new ValidationFailed('请指定数据库名。');
        }

        if (! is_string($this->database)) {
            throw new ValidationFailed('数据库名必须是一个非空字符串。');
        }

        if (empty($this->username)) {
            throw new ValidationFailed('请指定用于访问数据库的用户名。');
        }

        if (! is_string($this->database)) {
            throw new ValidationFailed('用户名必须是一个非空字符串。');
        }

        if (! empty($this->prefix)) {
            if (! preg_match('/^[\pL\pM\pN_]+$/u', $this->prefix)) {
                throw new ValidationFailed('前缀只能包含字母、数字和下划线。');
            }

            if (strlen($this->prefix) > 10) {
                throw new ValidationFailed('前缀不应超过10个字符。');
            }
        }
    }
}
