<?php

namespace Forumkit\Extend;

use DirectoryIterator;
use Forumkit\Extension\Extension;
use Forumkit\Extension\ExtensionManager;
use Forumkit\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Translation\MessageCatalogueInterface;

class LanguagePack implements ExtenderInterface, LifecycleInterface
{
    private const CORE_LOCALE_FILES = [
        'core',
        'validation',
    ];

    private $path;

    /**
     * LanguagePack 构造函数
     *
     * @param string $path: yaml语言文件的路径，默认为'/locale'
     */
    public function __construct(string $path = '/locale')
    {
        $this->path = $path;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (is_null($extension)) {
            throw new InvalidArgumentException(
                '我需要一个扩展实例来注册语言包'
            );
        }

        $locale = $extension->composerJsonAttribute('extra.forumkit-locale.code');
        $title = $extension->composerJsonAttribute('extra.forumkit-locale.title');

        if (! isset($locale, $title)) {
            throw new RuntimeException(
                '语言包必须在composer.json中定义 "extra.forumkit-locale.code" 和 "extra.forumkit-locale.title" 。'
            );
        }

        $container->resolving(
            LocaleManager::class,
            function (LocaleManager $locales, Container $container) use ($extension, $locale, $title) {
                $this->registerLocale($container, $locales, $extension, $locale, $title);
            }
        );
    }

    private function registerLocale(Container $container, LocaleManager $locales, Extension $extension, $locale, $title)
    {
        $locales->addLocale($locale, $title);

        $directory = $extension->getPath().$this->path;

        if (! is_dir($directory)) {
            throw new RuntimeException(
                '期望在语言包中找到 "'.$this->path.'" 目录。'
            );
        }

        // 如果存在config.js文件，则添加为JS文件
        if (file_exists($file = $directory.'/config.js')) {
            $locales->addJsFile($locale, $file);
        }

         // 如果存在config.css文件，则添加为CSS文件
        if (file_exists($file = $directory.'/config.css')) {
            $locales->addCssFile($locale, $file);
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if ($this->shouldLoad($file, $container)) {
                $locales->addTranslations($locale, $file->getPathname());
            }
        }
    }

    private function shouldLoad(SplFileInfo $file, Container $container)
    {
        if (! $file->isFile()) {
            return false;
        }

        // 我们只对YAML文件感兴趣
        if (! in_array($file->getExtension(), ['yml', 'yaml'], true)) {
            return false;
        }

        // 一些语言包包含了来自生态系统的许多扩展的翻译。出于性能考虑，我们应该只
        // 加载那些属于核心或已启用的扩展。
        // 为了识别它们，我们将文件名（不含YAML扩展名）与已知名称列表和所有扩展ID进行比较。
        $slug = $file->getBasename(".{$file->getExtension()}");

        // 忽略ICU MessageFormat后缀。
        $slug = str_replace(MessageCatalogueInterface::INTL_DOMAIN_SUFFIX, '', $slug);

        if (in_array($slug, self::CORE_LOCALE_FILES, true)) {
            return true;
        }

        /** @var ExtensionManager|null $extensions */
        static $extensions;
        $extensions = $extensions ?? $container->make(ExtensionManager::class);

        return $extensions->isEnabled($slug);
    }

    public function onEnable(Container $container, Extension $extension)
    {
        $container->make('forumkit.locales')->clearCache();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        $container->make('forumkit.locales')->clearCache();
    }
}
