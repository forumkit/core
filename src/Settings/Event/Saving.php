<?php

namespace Forumkit\Settings\Event;

class Saving
{
    /**
     * @var array
     */
    public $settings;

    /**
     * @param array $settings
     */
    public function __construct(array &$settings)
    {
        $this->settings = &$settings;
    }
}
