<?php

namespace Forumkit\Extension;

use Forumkit\Database\Migrator;
use Forumkit\Extension\Event\Disabled;
use Forumkit\Extension\Event\Disabling;
use Forumkit\Extension\Event\Enabled;
use Forumkit\Extension\Event\Enabling;
use Forumkit\Extension\Event\Uninstalled;
use Forumkit\Extension\Exception\CircularDependenciesException;
use Forumkit\Foundation\Paths;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ExtensionManager
{
    protected $config;

    /**
     * @var Paths
     */
    protected $paths;

    protected $container;

    protected $migrator;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Collection|null
     */
    protected $extensions;

    public function __construct(
        SettingsRepositoryInterface $config,
        Paths $paths,
        Container $container,
        Migrator $migrator,
        Dispatcher $dispatcher,
        Filesystem $filesystem
    ) {
        $this->config = $config;
        $this->paths = $paths;
        $this->container = $container;
        $this->migrator = $migrator;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
    }

    /**
     * @return Collection
     */
    public function getExtensions()
    {
        if (is_null($this->extensions) && $this->filesystem->exists($this->paths->vendor.'/composer/installed.json')) {
            $extensions = new Collection();

            // 加载所有通过 Composer 安装的包。
            $installed = json_decode($this->filesystem->get($this->paths->vendor.'/composer/installed.json'), true);

            // Composer 2.0 改变了 installed.json 清单的结构
            $installed = $installed['packages'] ?? $installed;

            // 我们计算并存储所有已安装的 Forumkit 扩展的 Composer 包名集合，
            // 以便在 `calculateDependencies` 方法中知道哪些是 Forumkit 扩展，哪些不是。
            // 使用关联数组的键允许我们在常数时间内进行这些检查。
            $installedSet = [];

            $composerJsonConfs = [];

            foreach ($installed as $package) {
                $name = Arr::get($package, 'name');
                if (empty($name)) {
                    continue;
                }

                $packagePath = isset($package['install-path'])
                    ? $this->paths->vendor.'/composer/'.$package['install-path']
                    : $this->paths->vendor.'/'.$name;

                if (Arr::get($package, 'type') === 'forumkit-extension') {
                    $composerJsonConfs[$packagePath] = $package;
                }

                if ($subextPaths = Arr::get($package, 'extra.forumkit-subextensions', [])) {
                    foreach ($subextPaths as $subExtPath) {
                        $subPackagePath = "$packagePath/$subExtPath";
                        $conf = json_decode($this->filesystem->get("$subPackagePath/composer.json"), true);

                        if (Arr::get($conf, 'type') === 'forumkit-extension') {
                            $composerJsonConfs[$subPackagePath] = $conf;
                        }
                    }
                }
            }

            foreach ($composerJsonConfs as $path => $package) {
                $installedSet[Arr::get($package, 'name')] = true;

                // 使用包的路径和 composer.json 文件实例化一个 Extension 对象。
                $extension = new Extension($path, $package);

                // 默认情况下，如果在 composer 中注册了扩展，则认为所有扩展都已安装。
                $extension->setInstalled(true);
                $extension->setVersion(Arr::get($package, 'version'));

                $extensions->put($extension->getId(), $extension);
            }

            foreach ($extensions as $extension) {
                $extension->calculateDependencies($installedSet);
            }

            $needsReset = false;
            $enabledExtensions = [];
            foreach ($this->getEnabled() as $enabledKey) {
                $extension = $extensions->get($enabledKey);
                if (is_null($extension)) {
                    $needsReset = true;
                } else {
                    $enabledExtensions[] = $extension;
                }
            }

            if ($needsReset) {
                $this->setEnabledExtensions($enabledExtensions);
            }

            $this->extensions = $extensions->sortBy(function ($extension, $name) {
                return $extension->getTitle();
            });
        }

        return $this->extensions;
    }

    public function getExtensionsById(array $ids): Collection
    {
        return $this->getExtensions()->filter(function (Extension $extension) use ($ids) {
            return in_array($extension->getId(), $ids);
        });
    }

    /**
     * 加载具有所有信息的扩展。
     *
     * @param string $name
     * @return Extension|null
     */
    public function getExtension($name)
    {
        return $this->getExtensions()->get($name);
    }

    /**
     * 启用扩展。
     *
     * @param string $name
     *
     * @internal
     */
    public function enable($name)
    {
        if ($this->isEnabled($name)) {
            return;
        }

        $extension = $this->getExtension($name);

        $missingDependencies = [];
        $enabledIds = $this->getEnabled();
        foreach ($extension->getExtensionDependencyIds() as $dependencyId) {
            if (! in_array($dependencyId, $enabledIds)) {
                $missingDependencies[] = $this->getExtension($dependencyId);
            }
        }

        if (! empty($missingDependencies)) {
            throw new Exception\MissingDependenciesException($extension, $missingDependencies);
        }

        $this->dispatcher->dispatch(new Enabling($extension));

        $this->migrate($extension);

        $this->publishAssets($extension);

        $enabledExtensions = $this->getEnabledExtensions();
        $enabledExtensions[] = $extension;
        $this->setEnabledExtensions($enabledExtensions);

        $extension->enable($this->container);

        $this->dispatcher->dispatch(new Enabled($extension));
    }

    /**
     * 禁用扩展。
     *
     * @param string $name
     *
     * @internal
     */
    public function disable($name)
    {
        $extension = $this->getExtension($name);
        $enabledExtensions = $this->getEnabledExtensions();

        if (($k = array_search($extension, $enabledExtensions)) === false) {
            return;
        }

        $dependentExtensions = [];

        foreach ($enabledExtensions as $possibleDependent) {
            if (in_array($extension->getId(), $possibleDependent->getExtensionDependencyIds())) {
                $dependentExtensions[] = $possibleDependent;
            }
        }

        if (! empty($dependentExtensions)) {
            throw new Exception\DependentExtensionsException($extension, $dependentExtensions);
        }

        $this->dispatcher->dispatch(new Disabling($extension));

        unset($enabledExtensions[$k]);
        $this->setEnabledExtensions($enabledExtensions);

        $extension->disable($this->container);

        $this->dispatcher->dispatch(new Disabled($extension));
    }

    /**
     * 卸载扩展。
     *
     * @param string $name
     * @internal
     */
    public function uninstall($name)
    {
        $extension = $this->getExtension($name);

        $this->disable($name);

        $this->migrateDown($extension);

        $this->unpublishAssets($extension);

        $extension->setInstalled(false);

        $this->dispatcher->dispatch(new Uninstalled($extension));
    }

    /**
     * 将扩展的资产目录中的资产复制到公共视图中。
     *
     * @param Extension $extension
     */
    protected function publishAssets(Extension $extension)
    {
        $extension->copyAssetsTo($this->getAssetsFilesystem());
    }

    /**
     * 从公共视图中删除扩展的资产。
     *
     * @param Extension $extension
     */
    protected function unpublishAssets(Extension $extension)
    {
        $this->getAssetsFilesystem()->deleteDirectory('extensions/'.$extension->getId());
    }

    /**
     * 获取已发布的扩展资产的路径。
     *
     * @param Extension $extension
     * @param string    $path
     * @return string
     */
    public function getAsset(Extension $extension, $path)
    {
        return $this->getAssetsFilesystem()->url($extension->getId()."/$path");
    }

    /**
     * 获取资产文件系统的实例。
     * 这是动态解析的，因为当ExtensionManager单例初始化时，Forumkit的文件系统配置可能尚未启动。
     */
    protected function getAssetsFilesystem(): Cloud
    {
        return resolve('filesystem')->disk('forumkit-assets');
    }

    /**
     * 运行扩展的数据库迁移。
     *
     * @param Extension $extension
     * @param string $direction
     * @return int
     *
     * @internal
     */
    public function migrate(Extension $extension, $direction = 'up')
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        return $extension->migrate($this->migrator, $direction);
    }

    /**
     * 运行数据库迁移以将数据库重置为其旧状态。
     *
     * @param Extension $extension
     * @return void
     *
     * @internal
     */
    public function migrateDown(Extension $extension)
    {
        $this->migrate($extension, 'down');
    }

    /**
     * 数据库迁移器。
     *
     * @return Migrator
     */
    public function getMigrator()
    {
        return $this->migrator;
    }

    /**
     * 仅获取已启用的扩展。
     *
     * @return array|Extension[]
     */
    public function getEnabledExtensions()
    {
        $enabled = [];
        $extensions = $this->getExtensions();

        foreach ($this->getEnabled() as $id) {
            if (isset($extensions[$id])) {
                $enabled[$id] = $extensions[$id];
            }
        }

        return $enabled;
    }

    /**
     * 调用所有已启用的扩展来扩展 Forumkit 应用程序。
     *
     * @param Container $container
     */
    public function extend(Container $container)
    {
        foreach ($this->getEnabledExtensions() as $extension) {
            $extension->extend($container);
        }
    }

    /**
     * 已启用扩展的ID列表。
     *
     * @return array
     */
    public function getEnabled()
    {
        return json_decode($this->config->get('extensions_enabled'), true) ?? [];
    }

    /**
     * 持久化当前已启用的扩展。
     *
     * @param array $enabledExtensions
     * @throws CircularDependenciesException
     */
    protected function setEnabledExtensions(array $enabledExtensions)
    {
        $resolved = static::resolveExtensionOrder($enabledExtensions);

        if (! empty($resolved['circularDependencies'])) {
            throw new Exception\CircularDependenciesException(
                $this->getExtensionsById($resolved['circularDependencies'])->values()->all()
            );
        }

        $sortedEnabled = $resolved['valid'];

        $sortedEnabledIds = array_map(function (Extension $extension) {
            return $extension->getId();
        }, $sortedEnabled);

        $this->config->set('extensions_enabled', json_encode($sortedEnabledIds));
    }

    /**
     * 判断扩展是否已启用。
     *
     * @param $extension
     * @return bool
     */
    public function isEnabled($extension)
    {
        $enabled = $this->getEnabledExtensions();

        return isset($enabled[$extension]);
    }

    /**
     * 返回传入扩展的标题列表。
     *
     * @param array $exts
     * @return string[]
     */
    public static function pluckTitles(array $exts)
    {
        return array_map(function (Extension $extension) {
            return $extension->getTitle();
        }, $exts);
    }

    /**
     * 对扩展列表进行排序，以便按照顺序正确解析。
     * 实际上就是拓扑排序。
     * 
     * @param Extension[] $extensionList 扩展列表
     *
     * @return array{valid: Extension[], missingDependencies: array<string, string[]>, circularDependencies: string[]}
     *      'valid' 指向一个有序的 \Forumkit\Extension\Extension 数组
     *      'missingDependencies' 指向一个关联数组，其中包含由于缺少依赖而无法解析的扩展。
     *      格式为扩展ID => 缺少的依赖项ID数组
     *      'circularDependencies' 指向一个数组，其中包含由于循环依赖而无法处理的扩展ID
     *
     * @internal
     */
    public static function resolveExtensionOrder($extensionList)
    {
        $extensionIdMapping = []; // 用于缓存，这样我们就不必每次都重新运行 ->getExtensions 方法。

        // 这是Kahn算法的实现 (https://dl.acm.org/doi/10.1145/368996.369025)
        $extensionGraph = [];
        $output = [];
        $missingDependencies = []; // 如果扩展缺少依赖项或存在循环依赖，则它们是无效的。
        $circularDependencies = [];
        $pendingQueue = [];
        $inDegreeCount = []; // 给定扩展有多少其他扩展依赖于它？

        // 按ID字母顺序排序。这样可以保证任何一组扩展都会以相同的方式排序。
        // 这使得启动顺序是确定的，并且与启用顺序无关。
        $extensionList = Arr::sort($extensionList, function ($ext) {
            return $ext->getId();
        });

        foreach ($extensionList as $extension) {
            $extensionIdMapping[$extension->getId()] = $extension;
        }

        /** @var Extension $extension */
        foreach ($extensionList as $extension) {
            $optionalDependencies = array_filter($extension->getOptionalDependencyIds(), function ($id) use ($extensionIdMapping) {
                return array_key_exists($id, $extensionIdMapping);
            });
            $extensionGraph[$extension->getId()] = array_merge($extension->getExtensionDependencyIds(), $optionalDependencies);

            foreach ($extensionGraph[$extension->getId()] as $dependency) {
                $inDegreeCount[$dependency] = array_key_exists($dependency, $inDegreeCount) ? $inDegreeCount[$dependency] + 1 : 1;
            }
        }

        foreach ($extensionList as $extension) {
            if (! array_key_exists($extension->getId(), $inDegreeCount)) {
                $inDegreeCount[$extension->getId()] = 0;
                $pendingQueue[] = $extension->getId();
            }
        }

        while (! empty($pendingQueue)) {
            $activeNode = array_shift($pendingQueue);
            $output[] = $activeNode;

            foreach ($extensionGraph[$activeNode] as $dependency) {
                $inDegreeCount[$dependency] -= 1;

                if ($inDegreeCount[$dependency] === 0) {
                    if (! array_key_exists($dependency, $extensionGraph)) {
                        // 缺少依赖关系
                        $missingDependencies[$activeNode] = array_merge(
                            Arr::get($missingDependencies, $activeNode, []),
                            [$dependency]
                        );
                    } else {
                        $pendingQueue[] = $dependency;
                    }
                }
            }
        }

        $validOutput = array_filter($output, function ($extension) use ($missingDependencies) {
            return ! array_key_exists($extension, $missingDependencies);
        });

        $validExtensions = array_reverse(array_map(function ($extensionId) use ($extensionIdMapping) {
            return $extensionIdMapping[$extensionId];
        }, $validOutput)); // 根据 Kahn 算法的要求进行反转。

        foreach ($inDegreeCount as $id => $count) {
            if ($count != 0) {
                $circularDependencies[] = $id;
            }
        }

        return [
            'valid' => $validExtensions,
            'missingDependencies' => $missingDependencies,
            'circularDependencies' => $circularDependencies
        ];
    }
}
