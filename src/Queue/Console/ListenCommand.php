<?php

namespace Forumkit\Queue\Console;

use Illuminate\Queue\Listener;

class ListenCommand extends \Illuminate\Queue\Console\ListenCommand
{
    public function __construct(Listener $listener)
    {
        parent::__construct($listener);

        $this->addOption('env');
    }
}
