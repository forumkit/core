<?php

namespace Forumkit\Install\Prerequisite;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WritablePaths implements PrerequisiteInterface
{
    /**
     * @var Collection
     */
    private $paths;

    private $wildcards = [];

    public function __construct(array $paths)
    {
        $this->paths = $this->normalize($paths);
    }

    public function problems(): Collection
    {
        return $this->getMissingPaths()
            ->concat($this->getNonWritablePaths());
    }

    private function getMissingPaths(): Collection
    {
        return $this->paths
            ->reject(function ($path) {
                return file_exists($path);
            })->map(function ($path) {
                return [
                    'message' => '/ '.$this->getAbsolutePath($path).' 目录不存在',
                    'detail' => '此目录是安装所必需的。请创建文件夹。',
                ];
            });
    }

    private function getNonWritablePaths(): Collection
    {
        return $this->paths
            ->filter(function ($path) {
                return file_exists($path) && ! is_writable($path);
            })->map(function ($path, $index) {
                return [
                    'message' => '/ '.$this->getAbsolutePath($path).' 目录不可写',
                    'detail' => '请确保您的 Web 服务器/PHP 用户对此目录具有写入权限 '.(in_array($index, $this->wildcards) ? ' 及其内容 ' : '').'. 阅读 <a href="http://www.forumkit.cn/install">安装文档</a> 了解解决此错误的详细说明和步骤。'
                ];
            });
    }

    private function getAbsolutePath($path)
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];

        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return (substr($path, 0, 1) == '/' ? '/' : '').implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    private function normalize(array $paths): Collection
    {
        return (new Collection($paths))
            ->map(function ($path, $index) {
                if (Str::endsWith($path, '/*')) {
                    $this->wildcards[] = $index;
                    $path = substr($path, 0, -2);
                }

                return $path;
            });
    }
}
