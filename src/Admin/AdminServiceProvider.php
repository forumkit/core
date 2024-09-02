<?php

namespace Forumkit\Admin;

use Forumkit\Extension\Event\Disabled;
use Forumkit\Extension\Event\Enabled;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\ErrorHandling\Registry;
use Forumkit\Foundation\ErrorHandling\Reporter;
use Forumkit\Foundation\ErrorHandling\ViewFormatter;
use Forumkit\Foundation\ErrorHandling\WhoopsFormatter;
use Forumkit\Foundation\Event\ClearingCache;
use Forumkit\Frontend\AddLocaleAssets;
use Forumkit\Frontend\AddTranslations;
use Forumkit\Frontend\Compiler\Source\SourceCollector;
use Forumkit\Frontend\RecompileFrontendAssets;
use Forumkit\Http\Middleware as HttpMiddleware;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;
use Forumkit\Http\UrlGenerator;
use Forumkit\Locale\LocaleManager;
use Forumkit\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class AdminServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('admin', $container->make('forumkit.admin.routes'), 'admin');
        });

        $this->container->singleton('forumkit.admin.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('forumkit.admin.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'forumkit.admin.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'forumkit.admin.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\RequireAdministrateAbility::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class,
                Middleware\DisableBrowserCache::class,
            ];
        });

        $this->container->bind('forumkit.admin.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['forumkit.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('forumkit.admin.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('forumkit.admin.routes'));
        });

        $this->container->singleton('forumkit.admin.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('forumkit.admin.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('forumkit.assets.admin', function (Container $container) {
            /** @var \Forumkit\Frontend\Assets $assets */
            $assets = $container->make('forumkit.assets.factory')('admin');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/admin.js');
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/admin.less');
            });

            $container->make(AddTranslations::class)->forFrontend('admin')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('forumkit.frontend.admin', function (Container $container) {
            /** @var \Forumkit\Frontend\Frontend $frontend */
            $frontend = $container->make('forumkit.frontend.factory')('admin');

            $frontend->content($container->make(Content\AdminPayload::class));

            return $frontend;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'forumkit.admin');

        $events = $this->container->make('events');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('forumkit.assets.admin'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('forumkit.assets.admin'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);
            }
        );
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
