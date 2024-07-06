<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Event\Disabled;
use Forumkit\Extension\Event\Enabled;
use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Forumkit\Foundation\Event\ClearingCache;
use Forumkit\Frontend\Assets;
use Forumkit\Frontend\Compiler\Source\SourceCollector;
use Forumkit\Frontend\Document;
use Forumkit\Frontend\Frontend as ActualFrontend;
use Forumkit\Frontend\RecompileFrontendAssets;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;
use Forumkit\Locale\LocaleManager;
use Forumkit\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;

class Frontend implements ExtenderInterface
{
    private $frontend;

    private $css = [];
    private $js;
    private $routes = [];
    private $removedRoutes = [];
    private $content = [];
    private $preloadArrs = [];
    private $titleDriver;

    /**
     * @param string $frontend: 前端的名称
     */
    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * 在前端添加一个CSS文件。
     *
     * @param string $path: CSS文件的路径
     * @return self
     */
    public function css(string $path): self
    {
        $this->css[] = $path;

        return $this;
    }

    /**
     * 在前端添加一个JavaScript文件。
     *
     * @param string $path: JavaScript文件的路径
     * @return self
     */
    public function js(string $path): self
    {
        $this->js = $path;

        return $this;
    }

    /**
     * 在前端添加一个路由。
     *
     * @param string $path: 路由的路径
     * @param string $name: 路由的名称，必须唯一
     * @param callable|string|null $content 路由的内容，可以为闭包、可调用类或者null
     *
     * 内容可以是一个闭包或可调用类，并应接受以下参数：
     * - \Forumkit\Frontend\Document $document
     * - \Psr\Http\Message\ServerRequestInterface $request
     *
     * 闭包应返回void
     *
     * @return self
     */
    public function route(string $path, string $name, $content = null): self
    {
        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    /**
     * 从前端移除一个路由。
     * 在覆盖路由之前，这是必要的步骤。
     *
     * @param string $name: 路由的名称
     * @return self
     */
    public function removeRoute(string $name): self
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    /**
     * 修改前端的内容。
     *
     * @param callable|string|null $callback 修改内容的闭包、可调用类或null
     *
     * 内容可以是一个闭包或可调用类，并应接受以下参数：
     * - \Forumkit\Frontend\Document $document
     * - \Psr\Http\Message\ServerRequestInterface $request
     *
     * 闭包应返回void
     *
     * @return self
     */
    public function content($callback): self
    {
        $this->content[] = $callback;

        return $this;
    }

    /**
     * 添加多个资源预加载。
     *
     * 参数应为一个包含预加载数组的数组，或者一个返回此数组的闭包。
     *
     * 预加载数组必须包含与 `<link rel="preload">` 标签相关的键。
     *
     * 例如，以下代码将添加一个脚本文件和字体文件的预加载标签：
     * ```
     * $frontend->preloads([
     *   [
     *     'href' => '/assets/my-script.js',
     *     'as' => 'script',
     *   ],
     *   [
     *     'href' => '/assets/fonts/my-font.woff2',
     *     'as' => 'font',
     *     'type' => 'font/woff2',
     *     'crossorigin' => ''
     *   ]
     * ]);
     * ```
     *
     * @param callable|array $preloads
     * @return self
     */
    public function preloads($preloads): self
    {
        $this->preloadArrs[] = $preloads;

        return $this;
    }

    /**
     * 注册新的标题驱动程序以更改前端文档的标题。
     */
    public function title(string $driverClass): self
    {
        $this->titleDriver = $driverClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
        $this->registerContent($container);
        $this->registerPreloads($container);
        $this->registerTitleDriver($container);
    }

    private function registerAssets(Container $container, string $moduleName): void
    {
        if (empty($this->css) && empty($this->js)) {
            return;
        }

        $abstract = 'forumkit.assets.'.$this->frontend;

        $container->resolving($abstract, function (Assets $assets) use ($moduleName) {
            if ($this->js) {
                $assets->js(function (SourceCollector $sources) use ($moduleName) {
                    $sources->addString(function () {
                        return 'var module={};';
                    });
                    $sources->addFile($this->js);
                    $sources->addString(function () use ($moduleName) {
                        return "forumkit.extensions['$moduleName']=module.exports;";
                    });
                });
            }

            if ($this->css) {
                $assets->css(function (SourceCollector $sources) use ($moduleName) {
                    foreach ($this->css as $path) {
                        $sources->addFile($path, $moduleName);
                    }
                });
            }
        });

        if (! $container->bound($abstract)) {
            $container->bind($abstract, function (Container $container) {
                return $container->make('forumkit.assets.factory')($this->frontend);
            });

            /** @var \Illuminate\Contracts\Events\Dispatcher $events */
            $events = $container->make('events');

            $events->listen(
                [Enabled::class, Disabled::class, ClearingCache::class],
                function () use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->flush();
                }
            );

            $events->listen(
                Saved::class,
                function (Saved $event) use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->whenSettingsSaved($event);
                }
            );
        }
    }

    private function registerRoutes(Container $container): void
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "forumkit.{$this->frontend}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->get(
                        $route['path'],
                        $route['name'],
                        $factory->toFrontend($this->frontend, $route['content'])
                    );
                }
            }
        );
    }

    private function registerContent(Container $container): void
    {
        if (empty($this->content)) {
            return;
        }

        $container->resolving(
            "forumkit.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                foreach ($this->content as $content) {
                    $frontend->content(ContainerUtil::wrapCallback($content, $container));
                }
            }
        );
    }

    private function registerPreloads(Container $container): void
    {
        if (empty($this->preloadArrs)) {
            return;
        }

        $container->resolving(
            "forumkit.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                $frontend->content(function (Document $document) use ($container) {
                    foreach ($this->preloadArrs as $preloadArr) {
                        $preloads = is_callable($preloadArr) ? ContainerUtil::wrapCallback($preloadArr, $container)($document) : $preloadArr;
                        $document->preloads = array_merge($document->preloads, $preloads);
                    }
                });
            }
        );
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }

    private function registerTitleDriver(Container $container): void
    {
        if ($this->titleDriver) {
            $container->extend('forumkit.frontend.title_driver', function ($driver, Container $container) {
                return $container->make($this->titleDriver);
            });
        }
    }
}
