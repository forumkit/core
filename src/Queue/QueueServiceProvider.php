<?php

namespace Forumkit\Queue;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\Config;
use Forumkit\Foundation\ErrorHandling\Registry;
use Forumkit\Foundation\ErrorHandling\Reporter;
use Forumkit\Foundation\Paths;
use Illuminate\Container\Container as ContainerImplementation;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Console as Commands;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Listener as QueueListener;
use Illuminate\Queue\SyncQueue;
use Illuminate\Queue\Worker;

class QueueServiceProvider extends AbstractServiceProvider
{
    protected $commands = [
        Commands\FlushFailedCommand::class,
        Commands\ForgetFailedCommand::class,
        Console\ListenCommand::class,
        Commands\ListFailedCommand::class,
        Commands\RestartCommand::class,
        Commands\RetryCommand::class,
        Console\WorkCommand::class,
    ];

    public function register()
    {
        // 注册一个简单的连接工厂，该工厂总是返回相同的连接，因为这对我们的目的来说已经足够了。
        $this->container->singleton(Factory::class, function (Container $container) {
            return new QueueFactory(function () use ($container) {
                return $container->make('forumkit.queue.connection');
            });
        });

        // 如果扩展想要让Forumkit使用不同的队列后端，则可以覆盖这个绑定。
        $this->container->singleton('forumkit.queue.connection', function (ContainerImplementation $container) {
            $queue = new SyncQueue;
            $queue->setContainer($container);

            return $queue;
        });

        $this->container->singleton(ExceptionHandling::class, function (Container $container) {
            return new ExceptionHandler($container['log']);
        });

        $this->container->singleton(Worker::class, function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            $worker = new Worker(
                $container[Factory::class],
                $container['events'],
                $container[ExceptionHandling::class],
                function () use ($config) {
                    return $config->inMaintenanceMode();
                }
            );

            $worker->setCache($container->make('cache.store'));

            return $worker;
        });

        // 重写 Laravel 原生的监听器，以便我们可以忽略环境选项并强制将二进制文件设为 forumkit。
        $this->container->singleton(QueueListener::class, function (Container $container) {
            return new Listener($container->make(Paths::class)->base);
        });

        // 绑定一个简单的缓存管理器，该管理器返回缓存存储。
        $this->container->singleton('cache', function (Container $container) {
            return new class($container) implements CacheFactory {
                /**
                 * @var Container
                 */
                private $container;

                public function __construct(Container $container)
                {
                    $this->container = $container;
                }

                public function driver()
                {
                    return $this->container['cache.store'];
                }

                // 我们必须明确定义此方法
                // 以实现接口。
                public function store($name = null)
                {
                    return $this->__call($name, null);
                }

                public function __call($name, $arguments)
                {
                    return call_user_func_array([$this->driver(), $name], $arguments);
                }
            };
        });

        $this->container->singleton('queue.failer', function () {
            return new NullFailedJobProvider();
        });

        $this->container->alias('forumkit.queue.connection', Queue::class);

        $this->container->alias(ConnectorInterface::class, 'queue.connection');
        $this->container->alias(Factory::class, 'queue');
        $this->container->alias(Worker::class, 'queue.worker');
        $this->container->alias(Listener::class, 'queue.listener');

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->container->extend('forumkit.console.commands', function ($commands, Container $container) {
            $queue = $container->make(Queue::class);

            // 当使用同步驱动时，不需要队列命令。
            if ($queue instanceof SyncQueue) {
                return $commands;
            }

            // 否则，添加我们的命令，同时允许它们被容器中已添加的命令覆盖。
            return array_merge($this->commands, $commands);
        });
    }

    public function boot(Dispatcher $events, Container $container)
    {
        $events->listen(JobFailed::class, function (JobFailed $event) use ($container) {
            /** @var Registry $registry */
            $registry = $container->make(Registry::class);

            $error = $registry->handle($event->exception);

            /** @var Reporter[] $reporters */
            $reporters = $container->tagged(Reporter::class);

            if ($error->shouldBeReported()) {
                foreach ($reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }
        });

        $events->subscribe(QueueRestarter::class);
    }
}
