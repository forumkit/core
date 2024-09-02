<?php

namespace Forumkit\Console;

use Forumkit\Console\Cache\Factory;
use Forumkit\Database\Console\MigrateCommand;
use Forumkit\Database\Console\ResetCommand;
use Forumkit\Extension\Console\ToggleExtensionCommand;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\Console\AssetsPublishCommand;
use Forumkit\Foundation\Console\CacheClearCommand;
use Forumkit\Foundation\Console\InfoCommand;
use Forumkit\Foundation\Console\ScheduleRunCommand;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Contracts\Container\Container;

class ConsoleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        // 用于 Laravel 代理 artisan 命令到其二进制文件。
        // Forumkit 使用类似的二进制文件，但命名为 forumkit 。
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'forumkit');
        }

        // Forumkit 不完全使用 Laravel 的缓存系统，而是创建并绑定一个单一的缓存存储。
        // 参见 \Forumkit\Foundation\InstalledSite::registerCache
        // 由于某些配置选项（例如 withoutOverlapping, onOneServer）需要缓存，
        // 我们必须覆盖我们给调度互斥锁提供的缓存工厂，使其返回我们自定义的单一缓存。
        $this->container->bind(EventMutex::class, function ($container) {
            return new CacheEventMutex($container->make(Factory::class));
        });
        $this->container->bind(SchedulingMutex::class, function ($container) {
            return new CacheSchedulingMutex($container->make(Factory::class));
        });

        $this->container->singleton(LaravelSchedule::class, function (Container $container) {
            return $container->make(Schedule::class);
        });

        $this->container->singleton('forumkit.console.commands', function () {
            return [
                AssetsPublishCommand::class,
                CacheClearCommand::class,
                InfoCommand::class,
                MigrateCommand::class,
                ResetCommand::class,
                ScheduleListCommand::class,
                ScheduleRunCommand::class,
                ToggleExtensionCommand::class
                // 以下是内部使用的命令，用于在主要发布前创建数据库转储
                // \Forumkit\Database\Console\GenerateDumpCommand::class
            ];
        });

        $this->container->singleton('forumkit.console.scheduled', function () {
            return [];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Container $container)
    {
        $schedule = $container->make(LaravelSchedule::class);

        foreach ($container->make('forumkit.console.scheduled') as $scheduled) {
            $event = $schedule->command($scheduled['command'], $scheduled['args']);
            $scheduled['callback']($event);
        }
    }
}
