<?php

namespace Forumkit\Foundation;

use Illuminate\Support\Arr;
use RuntimeException;

class Site
{
    /**
     * @param array $paths
     * @return SiteInterface
     */
    public static function fromPaths(array $paths)
    {
        $paths = new Paths($paths);

        date_default_timezone_set('UTC');

        if (! static::hasConfigFile($paths->base)) {
            // 对于新安装，实例化一个未安装的站点实例，
            // 如果在CLI环境中验证配置，则回退到localhost作为请求URI。
            return new UninstalledSite(
                $paths,
                Arr::get($_SERVER, 'REQUEST_URI', 'http://localhost')
            );
        }

        return (
            new InstalledSite($paths, static::loadConfig($paths->base))
        )->extendWith(static::loadExtenders($paths->base));
    }

    protected static function hasConfigFile($basePath)
    {
        return file_exists("$basePath/config.php");
    }

    protected static function loadConfig($basePath): Config
    {
        $config = include "$basePath/config.php";

        if (! is_array($config)) {
            throw new RuntimeException('config.php 应该返回一个数组');
        }

        return new Config($config);
    }

    protected static function loadExtenders($basePath): array
    {
        $extenderFile = "$basePath/extend.php";

        if (! file_exists($extenderFile)) {
            return [];
        }

        $extenders = require $extenderFile;

        if (! is_array($extenders)) {
            return [];
        }

        return Arr::flatten($extenders);
    }
}
