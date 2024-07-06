<?php

namespace Forumkit\Filter;

use Forumkit\Discussion\Filter\DiscussionFilterer;
use Forumkit\Discussion\Query as DiscussionQuery;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\ContainerUtil;
use Forumkit\Group\Filter as GroupFilter;
use Forumkit\Group\Filter\GroupFilterer;
use Forumkit\Http\Filter\AccessTokenFilterer;
use Forumkit\Http\Filter as HttpFilter;
use Forumkit\Post\Filter as PostFilter;
use Forumkit\Post\Filter\PostFilterer;
use Forumkit\User\Filter\UserFilterer;
use Forumkit\User\Query as UserQuery;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class FilterServiceProvider extends AbstractServiceProvider
{
    /**
     * 注册服务提供者。
     *
     * @return void
     */
    public function register()
    {
        $this->container->singleton('forumkit.filter.filters', function () {
            return [
                AccessTokenFilterer::class => [
                    HttpFilter\UserFilter::class,
                ],
                DiscussionFilterer::class => [
                    DiscussionQuery\AuthorFilterGambit::class,
                    DiscussionQuery\CreatedFilterGambit::class,
                    DiscussionQuery\HiddenFilterGambit::class,
                    DiscussionQuery\UnreadFilterGambit::class,
                ],
                UserFilterer::class => [
                    UserQuery\EmailFilterGambit::class,
                    UserQuery\GroupFilterGambit::class,
                ],
                GroupFilterer::class => [
                    GroupFilter\HiddenFilter::class,
                ],
                PostFilterer::class => [
                    PostFilter\AuthorFilter::class,
                    PostFilter\DiscussionFilter::class,
                    PostFilter\IdFilter::class,
                    PostFilter\NumberFilter::class,
                    PostFilter\TypeFilter::class
                ],
            ];
        });

        $this->container->singleton('forumkit.filter.filter_mutators', function () {
            return [];
        });
    }

    public function boot(Container $container)
    {
        // 我们可以在 when->needs->give 回调中解析过滤器修饰器，
        // 但我们至少需要解析一个，以便我们知道需要为哪些过滤器注册过滤器。
        $filters = $this->container->make('forumkit.filter.filters');

        foreach ($filters as $filterer => $filterClasses) {
            $container
                ->when($filterer)
                ->needs('$filters')
                ->give(function () use ($filterClasses) {
                    $compiled = [];

                    foreach ($filterClasses as $filterClass) {
                        $filter = $this->container->make($filterClass);
                        $compiled[$filter->getFilterKey()][] = $filter;
                    }

                    return $compiled;
                });

            $container
                ->when($filterer)
                ->needs('$filterMutators')
                ->give(function () use ($container, $filterer) {
                    return array_map(function ($filterMutatorClass) {
                        return ContainerUtil::wrapCallback($filterMutatorClass, $this->container);
                    }, Arr::get($container->make('forumkit.filter.filter_mutators'), $filterer, []));
                });
        }
    }
}
