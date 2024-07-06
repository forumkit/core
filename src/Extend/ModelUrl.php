<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class ModelUrl implements ExtenderInterface
{
    private $modelClass;
    private $slugDrivers = [];

    /**
     * @param string $modelClass: 你要修改的模型的::class属性
     *                           这个模型应该继承自 \Forumkit\Database\AbstractModel.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     *添加一个slug驱动器
     *
     * @param string $identifier: slug驱动器的标识符
     * @param string $driver: 驱动类的::class属性，该类必须实现 Forumkit\Http\SlugDriverInterface
     * @return self
     */
    public function addSlugDriver(string $identifier, string $driver): self
    {
        $this->slugDrivers[$identifier] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if ($this->slugDrivers) {
            $container->extend('forumkit.http.slugDrivers', function ($existingDrivers) {
                $existingDrivers[$this->modelClass] = array_merge(Arr::get($existingDrivers, $this->modelClass, []), $this->slugDrivers);

                return $existingDrivers;
            });
        }
    }
}
