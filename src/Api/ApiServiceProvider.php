<?php

namespace Forumkit\Api;

use Forumkit\Api\Controller\AbstractSerializeController;
use Forumkit\Api\Serializer\AbstractSerializer;
use Forumkit\Api\Serializer\BasicDiscussionSerializer;
use Forumkit\Api\Serializer\NotificationSerializer;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\ErrorHandling\JsonApiFormatter;
use Forumkit\Foundation\ErrorHandling\Registry;
use Forumkit\Foundation\ErrorHandling\Reporter;
use Forumkit\Http\Middleware as HttpMiddleware;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;
use Forumkit\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('api', $container->make('forumkit.api.routes'), 'api');
        });

        $this->container->singleton('forumkit.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('forumkit.api.throttlers', function () {
            return [
                'bypassThrottlingAttribute' => function ($request) {
                    if ($request->getAttribute('bypassThrottling')) {
                        return false;
                    }
                }
            ];
        });

        $this->container->bind(Middleware\ThrottleApi::class, function (Container $container) {
            return new Middleware\ThrottleApi($container->make('forumkit.api.throttlers'));
        });

        $this->container->singleton('forumkit.api.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'forumkit.api.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\SetLocale::class,
                'forumkit.api.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\ThrottleApi::class
            ];
        });

        $this->container->bind('forumkit.api.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                new JsonApiFormatter($container['forumkit.config']->inDebugMode()),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('forumkit.api.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('forumkit.api.routes'));
        });

        $this->container->singleton('forumkit.api.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($this->container->make('forumkit.api.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->singleton('forumkit.api.notification_serializers', function () {
            return [
                'discussionRenamed' => BasicDiscussionSerializer::class
            ];
        });

        $this->container->singleton('forumkit.api_client.exclude_middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\RememberFromCookie::class,
            ];
        });

        $this->container->singleton(Client::class, function ($container) {
            $pipe = new MiddlewarePipe;

            $exclude = $container->make('forumkit.api_client.exclude_middleware');

            $middlewareStack = array_filter($container->make('forumkit.api.middleware'), function ($middlewareClass) use ($exclude) {
                return ! in_array($middlewareClass, $exclude);
            });

            foreach ($middlewareStack as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return new Client($pipe);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container)
    {
        $this->setNotificationSerializers();

        AbstractSerializeController::setContainer($container);

        AbstractSerializer::setContainer($container);
    }

    /**
     * Register notification serializers.
     */
    protected function setNotificationSerializers()
    {
        $serializers = $this->container->make('forumkit.api.notification_serializers');

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
