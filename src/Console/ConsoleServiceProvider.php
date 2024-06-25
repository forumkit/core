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
        // Laravel 用于将 artisan 命令代理到其二进制文件。
        // Forumkit 使用类似的二进制文件，但它称为 forumkit。
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'forumkit');
        }

        // Forumkit 没有完全使用 Laravel 的缓存系统，而是使用创建并绑定单个缓存存储。
        // 请参阅 Forumkit\Foundation\InstalledSite::registerCache 由于某些配置选项（例如，withoutOverlapping，onOneServer）需要缓存，我们必须覆盖我们给调度的缓存 Factory 互斥锁，因此它返回我们的单个自定义缓存。
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
                ToggleExtensionCommand::class,
                // 内部使用，用于在主要版本发布前创建数据库备份。
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
