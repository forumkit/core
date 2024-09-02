<?php

namespace Forumkit\Group;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Group\Access\ScopeGroupVisibility;

class GroupServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        Group::registerVisibilityScoper(new ScopeGroupVisibility(), 'view');
    }
}
