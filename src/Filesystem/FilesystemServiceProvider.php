<?php

namespace Forumkit\Filesystem;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\Config;
use Forumkit\Foundation\Paths;
use Forumkit\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Intervention\Image\ImageManager;
use RuntimeException;

class FilesystemServiceProvider extends AbstractServiceProvider
{
    protected const INTERVENTION_DRIVERS = ['gd' => 'gd', 'imagick' => 'imagick'];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('files', function () {
            return new Filesystem;
        });

        $this->container->singleton('forumkit.filesystem.disks', function () {
            return [
                'forumkit-assets' => function (Paths $paths, UrlGenerator $url) {
                    return [
                        'root'   => "$paths->public/assets",
                        'url'    => $url->to('forum')->path('assets')
                    ];
                },
                'forumkit-avatars' => function (Paths $paths, UrlGenerator $url) {
                    return [
                        'root'   => "$paths->public/assets/avatars",
                        'url'    => $url->to('forum')->path('assets/avatars')
                    ];
                },
            ];
        });

        $this->container->singleton('forumkit.filesystem.drivers', function () {
            return [];
        });

        $this->container->singleton('forumkit.filesystem.resolved_drivers', function (Container $container) {
            return array_map(function ($driverClass) use ($container) {
                return $container->make($driverClass);
            }, $container->make('forumkit.filesystem.drivers'));
        });

        $this->container->singleton('filesystem', function (Container $container) {
            return new FilesystemManager(
                $container,
                $container->make('forumkit.filesystem.disks'),
                $container->make('forumkit.filesystem.resolved_drivers')
            );
        });

        $this->container->singleton(ImageManager::class, function (Container $container) {
            /** @var Config $config */
            $config = $this->container->make(Config::class);

            $intervention = $config->offsetGet('intervention');
            $driver = Arr::get($intervention, 'driver', self::INTERVENTION_DRIVERS['gd']);

            // Check that the imagick library is actually available, else default back to gd.
            if ($driver === self::INTERVENTION_DRIVERS['imagick'] && ! extension_loaded(self::INTERVENTION_DRIVERS['imagick'])) {
                $driver = self::INTERVENTION_DRIVERS['gd'];
            }

            if (! Arr::has(self::INTERVENTION_DRIVERS, $driver)) {
                throw new RuntimeException("intervention/image: $driver is not valid");
            }

            return new ImageManager([
                'driver' => $driver
            ]);
        });
    }
}
