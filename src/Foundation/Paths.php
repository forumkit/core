<?php

namespace Forumkit\Foundation;

use InvalidArgumentException;

/**
 * @property-read string $base
 * @property-read string $public
 * @property-read string $storage
 * @property-read string $vendor
 */
class Paths
{
    private $paths;

    public function __construct(array $paths)
    {
        if (! isset($paths['base'], $paths['public'], $paths['storage'])) {
            throw new InvalidArgumentException(
                'Paths 数组需要包含键 base、public 和 storage'
            );
        }

        $this->paths = array_map(function ($path) {
            return rtrim($path, '\/');
        }, $paths);

        // 除非指定，否则采用标准的 Composer 目录结构
        $this->paths['vendor'] = $this->vendor ?? $this->base.'/vendor';
    }

    public function __get($name): ?string
    {
        return $this->paths[$name] ?? null;
    }
}
