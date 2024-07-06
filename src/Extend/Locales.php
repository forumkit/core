<?php

namespace Forumkit\Extend;

use DirectoryIterator;
use Forumkit\Extension\Extension;
use Forumkit\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Translation\MessageCatalogueInterface;

class Locales implements ExtenderInterface, LifecycleInterface
{
    private $directory;

    /**
     * @param string $directory: 区域设置文件的目录
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving(
            LocaleManager::class,
            function (LocaleManager $locales) {
                foreach (new DirectoryIterator($this->directory) as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }

                    $extension = $file->getExtension();
                    if (! in_array($extension, ['yml', 'yaml'])) {
                        continue;
                    }

                    $locale = $file->getBasename(".$extension");

                    // 忽略 ICU MessageFormat 后缀。
                    $locale = str_replace(MessageCatalogueInterface::INTL_DOMAIN_SUFFIX, '', $locale);

                    $locales->addTranslations(
                        $locale,
                        $file->getPathname()
                    );
                }
            }
        );
    }

    public function onEnable(Container $container, Extension $extension)
    {
        $container->make(LocaleManager::class)->clearCache();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        $container->make(LocaleManager::class)->clearCache();
    }
}
