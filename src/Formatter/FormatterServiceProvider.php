<?php

namespace Forumkit\Formatter;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\Paths;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Container\Container;

class FormatterServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('forumkit.formatter', function (Container $container) {
            return new Formatter(
                new Repository($container->make('cache.filestore')),
                $container[Paths::class]->storage.'/formatter'
            );
        });

        $this->container->alias('forumkit.formatter', Formatter::class);
    }
}
