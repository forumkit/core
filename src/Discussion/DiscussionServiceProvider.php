<?php

namespace Forumkit\Discussion;

use Forumkit\Discussion\Access\ScopeDiscussionVisibility;
use Forumkit\Discussion\Event\Renamed;
use Forumkit\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionServiceProvider extends AbstractServiceProvider
{
    public function boot(Dispatcher $events)
    {
        $events->subscribe(DiscussionMetadataUpdater::class);
        $events->subscribe(UserStateUpdater::class);

        $events->listen(
            Renamed::class,
            DiscussionRenamedLogger::class
        );

        Discussion::registerVisibilityScoper(new ScopeDiscussionVisibility(), 'view');
    }
}
