<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\Factory as FactoryImplementation;

/**
 * 视图是使用 Laravel Blade 语法创建的服务器端生成的 HTML 的 PHP 文件。
 *
 * Forumkit 的核心使用它们来生成错误页面、安装程序、HTML 电子邮件以及网站和管理员站点的骨架。
 */
class View implements ExtenderInterface, LifecycleInterface
{
    private $namespaces = [];
    private $prependNamespaces = [];

    /**
     * 注册一个新的 Laravel 视图命名空间。
     *
     * 要在您的扩展中创建和使用视图，您需要将它们放在一个文件夹中，并将该文件夹注册为一个命名空间。
     *
     * 然后，您可以在您的扩展中通过注入 `Illuminate\Contracts\View\Factory`,的实例，并调用其 `make` 方法来使用视图。
     * `make` 方法采用 NAMESPACE::VIEW_NAME 格式的视图参数。您还可以将变量传递到视图中。 
     * 更多信息，请参见： https://laravel.com/api/8.x/Illuminate/View/Factory.html#method_make
     *
     * @param  string  $namespace: 命名空间的名称
     * @param  string|string[]  $hints: 这是相对于 extend.php 文件的视图文件存储的文件夹（或文件夹数组）的路径
     * @return self
     */
    public function namespace(string $namespace, $hints): self
    {
        $this->namespaces[$namespace] = $hints;

        return $this;
    }

    /**
     * 扩展一个现有的 Laravel 视图命名空间。
     *
     * 要扩展一个现有的命名空间，您需要在您的扩展中将视图放在一个文件夹中，
     * 并使用此扩展器将该文件夹注册到现有命名空间下。
     *
     * @param  string  $namespace: 命名空间的名称
     * @param  string|string[]  $hints: 这是相对于 extend.php 文件的视图文件存储的文件夹（或文件夹数组）的路径
     * @return self
     */
    public function extendNamespace(string $namespace, $hints): self
    {
        $this->prependNamespaces[$namespace] = $hints;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving(Factory::class, function (FactoryImplementation $view) {
            foreach ($this->namespaces as $namespace => $hints) {
                $view->addNamespace($namespace, $hints);
            }
            foreach ($this->prependNamespaces as $namespace => $hints) {
                $view->prependNamespace($namespace, $hints);
            }
        });
    }

    /**
     * @param Container $container
     * @param Extension $extension
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function onEnable(Container $container, Extension $extension)
    {
        $storagePath = $container->make(Paths::class)->storage;
        array_map('unlink', glob($storagePath.'/views/*'));
    }

    /**
     * @param Container $container
     * @param Extension $extension
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function onDisable(Container $container, Extension $extension)
    {
        $storagePath = $container->make(Paths::class)->storage;
        array_map('unlink', glob($storagePath.'/views/*'));
    }
}
