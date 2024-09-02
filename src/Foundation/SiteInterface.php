<?php

namespace Forumkit\Foundation;

interface SiteInterface
{
    /**
     * Create and boot a Forumkit application instance.
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface;
}
