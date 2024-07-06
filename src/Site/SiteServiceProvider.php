<?php

namespace Forumkit\Site;

use Forumkit\Extension\Event\Disabled;
use Forumkit\Extension\Event\Enabled;
use Forumkit\Formatter\Formatter;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\ErrorHandling\Registry;
use Forumkit\Foundation\ErrorHandling\Reporter;
use Forumkit\Foundation\ErrorHandling\ViewFormatter;
use Forumkit\Foundation\ErrorHandling\WhoopsFormatter;
use Forumkit\Foundation\Event\ClearingCache;
use Forumkit\Frontend\AddLocaleAssets;
use Forumkit\Frontend\AddTranslations;
use Forumkit\Frontend\Assets;
use Forumkit\Frontend\Compiler\Source\SourceCollector;
use Forumkit\Frontend\RecompileFrontendAssets;
use Forumkit\Http\Middleware as HttpMiddleware;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;
use Forumkit\Http\UrlGenerator;
use Forumkit\Locale\LocaleManager;
use Forumkit\Settings\Event\Saved;
use Forumkit\Settings\Event\Saving;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Laminas\Stratigility\MiddlewarePipe;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('site', $container->make('forumkit.site.routes'));
        });

        $this->container->singleton('forumkit.site.routes', function (Container $container) {
            $routes = new RouteCollection;
            $this->populateRoutes($routes, $container);

            return $routes;
        });

        $this->container->afterResolving('forumkit.site.routes', function (RouteCollection $routes, Container $container) {
            $this->setDefaultRoute($routes, $container);
        });

        $this->container->singleton('forumkit.site.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'forumkit.site.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\CollectGarbage::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'forumkit.site.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\ShareErrorsFromSession::class,
                HttpMiddleware\ForumkitPromotionHeader::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class
            ];
        });

        $this->container->bind('forumkit.site.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['forumkit.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('forumkit.site.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('forumkit.site.routes'));
        });

        $this->container->singleton('forumkit.site.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('forumkit.site.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('forumkit.assets.site', function (Container $container) {
            /** @var Assets $assets */
            $assets = $container->make('forumkit.assets.factory')('site');

            $assets->js(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../js/dist/site.js');
                $sources->addString(function () use ($container) {
                    return $container->make(Formatter::class)->getJs();
                });
            });

            $assets->css(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../less/site.less');
                $sources->addString(function () use ($container) {
                    return $container->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $container->make(AddTranslations::class)->forFrontend('site')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('forumkit.frontend.site', function (Container $container) {
            return $container->make('forumkit.frontend.factory')('site');
        });

        $this->container->singleton('forumkit.site.discussions.sortmap', function () {
            return [
                'latest' => '-lastPostedAt',
                'top' => '-commentCount',
                'newest' => '-createdAt',
                'oldest' => 'createdAt'
            ];
        });
    }

    public function boot(Container $container, Dispatcher $events, Factory $view)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'forumkit.site');

        $view->share([
            'translator' => $container->make(TranslatorInterface::class),
            'settings' => $container->make(SettingsRepositoryInterface::class)
        ]);

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('forumkit.assets.site'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('forumkit.assets.site'),
                    $container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $container->make('forumkit.assets.site'),
                    $container->make('forumkit.locales'),
                    $container,
                    $container->make('forumkit.less.config')
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) use ($container) {
                $validator = new ValidateCustomLess(
                    $container->make('forumkit.assets.site'),
                    $container->make('forumkit.locales'),
                    $container,
                    $container->make('forumkit.less.config')
                );
                $validator->whenSettingsSaving($event);
            }
        );
    }

    /**
     * 填充站点客户端路由。
     *
     * @param RouteCollection $routes
     * @param Container       $container
     */
    protected function populateRoutes(RouteCollection $routes, Container $container)
    {
        $factory = $container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }

    /**
     * 确定默认路由。
     *
     * @param RouteCollection $routes
     * @param Container       $container
     */
    protected function setDefaultRoute(RouteCollection $routes, Container $container)
    {
        $factory = $container->make(RouteHandlerFactory::class);
        $defaultRoute = $container->make('forumkit.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute]['handler'])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute]['handler'];
        } else {
            $toDefaultController = $factory->toSite(Content\Index::class);
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );
    }
}
