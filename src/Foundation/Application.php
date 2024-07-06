<?php

namespace Forumkit\Foundation;

use Illuminate\Contracts\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class Application
{
    /**
     * Forumkit的版本号。
     *
     * @var string
     */
    const VERSION = '0.9.0';

    /**
     * Forumkit应用程序的IoC容器。
     *
     * @var Container
     */
    private $container;

    /**
     * Forumkit安装的路径。
     *
     * @var Paths
     */
    protected $paths;

    /**
     * 指示应用程序是否已 "booted"。
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * 引导回调的数组。
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * 启动回调的数组。
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * 所有已注册的服务提供者。
     *
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * 已加载的服务提供者的名称。
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * 创建一个新的Forumkit应用程序实例。
     *
     * @param Container $container
     * @param Paths $paths
     */
    public function __construct(Container $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        $config = $this->container->make('forumkit.config');

        return $config[$key] ?? $default;
    }

    /**
     * 检查Forumkit是否处于调试模式。
     *
     * @return bool
     */
    public function inDebugMode()
    {
        return $this->config('debug', true);
    }

    /**
     * 获取到Forumkit安装的URL。
     *
     * @param string $path
     * @return string
     */
    public function url($path = null)
    {
        $config = $this->container->make('forumkit.config');
        $url = (string) $config->url();

        if ($path) {
            $url .= '/'.($config["paths.$path"] ?? $path);
        }

        return $url;
    }

    /**
     * 在容器中注册基本绑定。
     */
    protected function registerBaseBindings()
    {
        \Illuminate\Container\Container::setInstance($this->container);

        /**
         * Laravel框架代码所需。
         * 在Forumkit内部使用容器。
         */
        $this->container->instance('app', $this->container);
        $this->container->alias('app', \Illuminate\Container\Container::class);

        $this->container->instance('container', $this->container);
        $this->container->alias('container', \Illuminate\Container\Container::class);

        $this->container->instance('forumkit', $this);
        $this->container->alias('forumkit', self::class);

        $this->container->instance('forumkit.paths', $this->paths);
        $this->container->alias('forumkit.paths', Paths::class);
    }

    /**
     * 注册所有基本服务提供者。
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this->container));
    }

    /**
     * 使用应用程序注册服务提供者。
     *
     * @param ServiceProvider|string $provider
     * @param array $options
     * @param bool $force
     * @return ServiceProvider
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // 如果给定的 "provider" 是字符串，则解析它，并自动将应用程序实例传递给开发人员。
        // 这是一种更方便的指定服务提供者类的方式。
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        // 注册服务后，我们将遍历选项，并将它们设置到应用程序上，
        // 以便在实际加载服务对象和开发人员使用时可用。
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        // 如果应用程序已经启动，我们将调用服务提供者的启动方法，
        // 以便它有机会执行其启动逻辑，并为开发人员的应用程序逻辑做好准备。
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * 如果存在，获取已注册的服务提供者实例。
     *
     * @param ServiceProvider|string $provider
     * @return ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::first($this->serviceProviders, function ($key, $value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * 根据类名解析服务提供者实例。
     *
     * @param string $provider
     * @return ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this->container);
    }

    /**
     * 将给定的服务提供者标记为已注册。
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->container['events']->dispatch($class = get_class($provider), [$provider]);

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * 确定应用程序是否已启动。
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * 启动应用程序的服务提供者。
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        // 在应用程序启动后，我们将触发一些 "booted" 回调，
        // 以供需要在初始启动完成后执行工作的监听器使用。
        // 这在排序我们运行的启动过程时很有用。
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * 启动给定的服务提供者。
     *
     * @param ServiceProvider $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->container->call([$provider, 'boot']);
        }
    }

    /**
     * 注册一个新的启动监听器。
     *
     * @param mixed $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * 注册一个新的 "booted" 监听器。
     *
     * @param mixed $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * 调用应用程序的启动回调。
     *
     * @param array $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * 在容器中注册核心类的别名。
     */
    public function registerCoreContainerAliases()
    {
        $aliases = [
            'app'                  => [\Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class,  \Psr\Container\ContainerInterface::class],
            'blade.compiler'       => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache'                => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.store'          => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class],
            'config'               => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'db'                   => [\Illuminate\Database\DatabaseManager::class],
            'db.connection'        => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
            'events'               => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files'                => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem'           => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk'      => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud'     => [\Illuminate\Contracts\Filesystem\Cloud::class],
            'hash'                 => [\Illuminate\Contracts\Hashing\Hasher::class],
            'mailer'               => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'validator'            => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view'                 => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->container->alias($key, $alias);
            }
        }
    }
}
