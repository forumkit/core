<?php

namespace Forumkit\Install\Console;

use Forumkit\Install\Installation;

interface DataProviderInterface
{
    public function configure(Installation $installation): Installation;
}
