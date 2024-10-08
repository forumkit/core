<?php

namespace Forumkit\Console\Cache;

use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;

class Factory implements FactoryContract
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 按名称获取缓存存储实例
     *
     * @param  string|null $name
     * @return Repository
     */
    public function store($name = null)
    {
        return $this->container['cache.store'];
    }
}
