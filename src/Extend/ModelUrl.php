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
     * @param string $modelClass: The ::class attribute of the model you are modifying.
     *                           This model should extend from \Forumkit\Database\AbstractModel.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Add a slug driver.
     *
     * @param string $identifier: Identifier for slug driver.
     * @param string $driver: ::class attribute of driver class, which must implement Forumkit\Http\SlugDriverInterface.
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
