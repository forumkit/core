<?php

namespace Forumkit\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AbstractJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
}
