<?php

namespace Forumkit\Locale;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\Paths;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton(LocaleManager::class, function (Container $container) {
            $locales = new LocaleManager(
                $container->make('translator'),
                $this->getCacheDir($container)
            );

            $locales->addLocale($this->getDefaultLocale($container), 'Default');
            $locales->addTranslations('zh', __DIR__.'/../../locale/core.yml');
            $locales->addTranslations('zh', __DIR__.'/../../locale/validation.yml');

            return $locales;
        });

        $this->container->alias(LocaleManager::class, 'forumkit.locales');

        $this->container->singleton('translator', function (Container $container) {
            $translator = new Translator(
                $this->getDefaultLocale($container),
                null,
                $this->getCacheDir($container),
                $container['forumkit.debug']
            );

            $translator->setFallbackLocales(['zh']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());

            return $translator;
        });

        $this->container->alias('translator', Translator::class);
        $this->container->alias('translator', TranslatorContract::class);
        $this->container->alias('translator', TranslatorInterface::class);
    }

    private function getDefaultLocale(Container $container): string
    {
        $repo = $container->make(SettingsRepositoryInterface::class);

        return $repo->get('default_locale', 'zh');
    }

    private function getCacheDir(Container $container): string
    {
        return $container[Paths::class]->storage.'/locale';
    }
}
