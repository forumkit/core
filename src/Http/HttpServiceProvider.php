<?php

namespace Forumkit\Http;

use Forumkit\Discussion\Discussion;
use Forumkit\Discussion\IdWithTransliteratedSlugDriver;
use Forumkit\Discussion\Utf8SlugDriver;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Http\Access\ScopeAccessTokenVisibility;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\IdSlugDriver;
use Forumkit\User\User;
use Forumkit\User\UsernameSlugDriver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('forumkit.http.csrfExemptPaths', function () {
            return ['token'];
        });

        $this->container->bind(Middleware\CheckCsrfToken::class, function (Container $container) {
            return new Middleware\CheckCsrfToken($container->make('forumkit.http.csrfExemptPaths'));
        });

        $this->container->singleton('forumkit.http.slugDrivers', function () {
            return [
                Discussion::class => [
                    'default' => IdWithTransliteratedSlugDriver::class,
                    'utf8' => Utf8SlugDriver::class,
                ],
                User::class => [
                    'default' => UsernameSlugDriver::class,
                    'id' => IdSlugDriver::class
                ],
            ];
        });

        $this->container->singleton('forumkit.http.selectedSlugDrivers', function (Container $container) {
            $settings = $container->make(SettingsRepositoryInterface::class);

            $compiledDrivers = [];

            foreach ($container->make('forumkit.http.slugDrivers') as $resourceClass => $resourceDrivers) {
                $driverKey = $settings->get("slug_driver_$resourceClass", 'default');

                $driverClass = Arr::get($resourceDrivers, $driverKey, $resourceDrivers['default']);

                $compiledDrivers[$resourceClass] = $container->make($driverClass);
            }

            return $compiledDrivers;
        });
        $this->container->bind(SlugManager::class, function (Container $container) {
            return new SlugManager($container->make('forumkit.http.selectedSlugDrivers'));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->setAccessTokenTypes();

        AccessToken::registerVisibilityScoper(new ScopeAccessTokenVisibility(), 'view');
    }

    protected function setAccessTokenTypes()
    {
        $models = [
            DeveloperAccessToken::class,
            RememberAccessToken::class,
            SessionAccessToken::class
        ];

        foreach ($models as $model) {
            AccessToken::setModel($model::$type, $model);
        }
    }
}
