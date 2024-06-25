<?php

namespace Forumkit\Extension\Event;

use Forumkit\Extension\Extension;

class Enabling
{
    /**
     * @var Extension
     */
    public $extension;

    /**
     * @param Extension $extension
     */
    public function __construct(Extension $extension)
    {
        $this->extension = $extension;
    }
}
