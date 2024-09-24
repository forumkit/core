<?php

namespace Forumkit\Settings;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Settings\Event\Saving;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class SettingsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('forumkit.settings.default', function () {
            return new Collection([
                'theme_primary_color' => '#1E87F0',
                'theme_secondary_color' => '#1E87F0',
            ]);
        });

        $this->container->singleton(SettingsRepositoryInterface::class, function (Container $container) {
            return new DefaultSettingsRepository(
                new MemoryCacheSettingsRepository(
                    new DatabaseSettingsRepository(
                        $container->make(ConnectionInterface::class)
                    )
                ),
                $container->make('forumkit.settings.default')
            );
        });

        $this->container->alias(SettingsRepositoryInterface::class, 'forumkit.settings');
    }

    public function boot(Dispatcher $events, SettingsValidator $settingsValidator)
    {
        $events->listen(
            Saving::class,
            function (Saving $event) use ($settingsValidator) {
                $settingsValidator->assertValid($event->settings);
            }
        );
    }
}
