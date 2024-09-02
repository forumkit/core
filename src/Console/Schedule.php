<?php

namespace Forumkit\Console;

use Forumkit\Foundation\Config;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Support\Collection;

class Schedule extends LaravelSchedule
{
    public function dueEvents($container)
    {
        return (new Collection($this->events))->filter->isDue(new class($container) {
            /** @var Config */
            protected $config;

            public function __construct($container)
            {
                $this->config = $container->make(Config::class);
            }

            public function isDownForMaintenance(): bool
            {
                return $this->config->inMaintenanceMode();
            }

            public function environment(): string
            {
                return '';
            }
        });
    }
}
