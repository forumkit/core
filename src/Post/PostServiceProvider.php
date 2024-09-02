<?php

namespace Forumkit\Post;

use Forumkit\Formatter\Formatter;
use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Post\Access\ScopePostVisibility;
use Illuminate\Contracts\Container\Container;

class PostServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend('forumkit.api.throttlers', function (array $throttlers, Container $container) {
            $throttlers['postTimeout'] = $container->make(PostCreationThrottler::class);

            return $throttlers;
        });
    }

    public function boot(Formatter $formatter)
    {
        CommentPost::setFormatter($formatter);

        $this->setPostTypes();

        Post::registerVisibilityScoper(new ScopePostVisibility(), 'view');
    }

    protected function setPostTypes()
    {
        $models = [
            CommentPost::class,
            DiscussionRenamedPost::class
        ];

        foreach ($models as $model) {
            Post::setModel($model::$type, $model);
        }
    }
}
