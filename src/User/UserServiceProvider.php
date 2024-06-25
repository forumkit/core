<?php

namespace Forumkit\User;

use Forumkit\Discussion\Access\DiscussionPolicy;
use Forumkit\Discussion\Discussion;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\ContainerUtil;
use Forumkit\Group\Access\GroupPolicy;
use Forumkit\Group\Group;
use Forumkit\Http\Access\AccessTokenPolicy;
use Forumkit\Http\AccessToken;
use Forumkit\Post\Access\PostPolicy;
use Forumkit\Post\Post;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\Access\ScopeUserVisibility;
use Forumkit\User\DisplayName\DriverInterface;
use Forumkit\User\DisplayName\UsernameDriver;
use Forumkit\User\Event\EmailChangeRequested;
use Forumkit\User\Event\Registered;
use Forumkit\User\Event\Saving;
use Forumkit\User\Throttler\EmailActivationThrottler;
use Forumkit\User\Throttler\EmailChangeThrottler;
use Forumkit\User\Throttler\PasswordResetThrottler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerDisplayNameDrivers();
        $this->registerPasswordCheckers();

        $this->container->singleton('forumkit.user.group_processors', function () {
            return [];
        });

        $this->container->singleton('forumkit.policies', function () {
            return [
                Access\AbstractPolicy::GLOBAL => [],
                AccessToken::class => [AccessTokenPolicy::class],
                Discussion::class => [DiscussionPolicy::class],
                Group::class => [GroupPolicy::class],
                Post::class => [PostPolicy::class],
                User::class => [Access\UserPolicy::class],
            ];
        });

        $this->container->extend('forumkit.api.throttlers', function (array $throttlers, Container $container) {
            $throttlers['emailChangeTimeout'] = $container->make(EmailChangeThrottler::class);
            $throttlers['emailActivationTimeout'] = $container->make(EmailActivationThrottler::class);
            $throttlers['passwordResetTimeout'] = $container->make(PasswordResetThrottler::class);

            return $throttlers;
        });
    }

    protected function registerDisplayNameDrivers()
    {
        $this->container->singleton('forumkit.user.display_name.supported_drivers', function () {
            return [
                'username' => UsernameDriver::class,
            ];
        });

        $this->container->singleton('forumkit.user.display_name.driver', function (Container $container) {
            $drivers = $container->make('forumkit.user.display_name.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('display_name_driver', '');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $container->make($driverClass)
                : $container->make(UsernameDriver::class);
        });

        $this->container->alias('forumkit.user.display_name.driver', DriverInterface::class);
    }

    protected function registerPasswordCheckers()
    {
        $this->container->singleton('forumkit.user.password_checkers', function (Container $container) {
            return [
                'standard' => function (User $user, $password) use ($container) {
                    if ($container->make('hash')->check($password, $user->password)) {
                        return true;
                    }
                }
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, Dispatcher $events)
    {
        foreach ($container->make('forumkit.user.group_processors') as $callback) {
            User::addGroupProcessor(ContainerUtil::wrapCallback($callback, $container));
        }

        /**
         * @var \Illuminate\Container\Container $container
         */
        User::setHasher($container->make('hash'));
        User::setPasswordCheckers($container->make('forumkit.user.password_checkers'));
        User::setGate($container->makeWith(Access\Gate::class, ['policyClasses' => $container->make('forumkit.policies')]));
        User::setDisplayNameDriver($container->make('forumkit.user.display_name.driver'));

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);
        $events->subscribe(TokensClearer::class);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
        User::registerPreference('locale');

        User::registerVisibilityScoper(new ScopeUserVisibility(), 'view');
    }
}
