<?php

namespace Forumkit\Site;

use Forumkit\Foundation\ValidationException;
use Forumkit\Frontend\Assets;
use Forumkit\Locale\LocaleManager;
use Forumkit\Settings\Event\Saved;
use Forumkit\Settings\Event\Saving;
use Forumkit\Settings\OverrideSettingsRepository;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use Less_Exception_Parser;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class ValidateCustomLess
{
    /**
     * @var Assets
     */
    protected $assets;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $customLessSettings;

    public function __construct(Assets $assets, LocaleManager $locales, Container $container, array $customLessSettings = [])
    {
        $this->assets = $assets;
        $this->locales = $locales;
        $this->container = $container;
        $this->customLessSettings = $customLessSettings;
    }

    public function whenSettingsSaving(Saving $event)
    {
        if (! isset($event->settings['custom_less']) && ! $this->hasDirtyCustomLessSettings($event)) {
            return;
        }

        // 限制在自定义LESS中可以使用的功能
        if (isset($event->settings['custom_less']) && preg_match('/@import|data-uri\s*\(/i', $event->settings['custom_less'])) {
            $translator = $this->container->make(TranslatorInterface::class);

            throw new ValidationException([
                'custom_less' => $translator->trans('core.admin.appearance.custom_styles_cannot_use_less_features')
            ]);
        }

        // 我们还没有保存设置，但想要尝试完整重新编译CSS，以查看这个自定义的LESS是否会破坏任何东西。
        // 为了实现这一点，我们将临时用新的设置覆盖设置仓库，这样重新编译才会生效。
        // 我们还将使用一个虚拟的文件系统，以确保在此过程中不会实际写入任何内容。

        $settings = $this->container->make(SettingsRepositoryInterface::class);

        $this->container->extend(
            SettingsRepositoryInterface::class,
            function ($settings) use ($event) {
                return new OverrideSettingsRepository($settings, $event->settings);
            }
        );

        $assetsDir = $this->assets->getAssetsDir();
        $this->assets->setAssetsDir(new FilesystemAdapter(new Filesystem(new NullAdapter)));

        try {
            $this->assets->makeCss()->commit();

            foreach ($this->locales->getLocales() as $locale => $name) {
                $this->assets->makeLocaleCss($locale)->commit();
            }
        } catch (Less_Exception_Parser $e) {
            throw new ValidationException(['custom_less' => $e->getMessage()]);
        }

        $this->assets->setAssetsDir($assetsDir);
        $this->container->instance(SettingsRepositoryInterface::class, $settings);
    }

    public function whenSettingsSaved(Saved $event)
    {
        if (! isset($event->settings['custom_less']) && ! $this->hasDirtyCustomLessSettings($event)) {
            return;
        }

        $this->assets->makeCss()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleCss($locale)->flush();
        }
    }

    /**
     * @param Saved|Saving $event
     * @return bool
     */
    protected function hasDirtyCustomLessSettings($event): bool
    {
        if (empty($this->customLessSettings)) {
            return false;
        }

        $dirtySettings = array_intersect(
            array_keys($event->settings),
            array_map(function ($setting) {
                return $setting['key'];
            }, $this->customLessSettings)
        );

        return ! empty($dirtySettings);
    }
}
