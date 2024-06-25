<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;

interface LifecycleInterface
{
    public function onEnable(Container $container, Extension $extension);

    public function onDisable(Container $container, Extension $extension);
}
