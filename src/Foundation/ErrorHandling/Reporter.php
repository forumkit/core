<?php

namespace Forumkit\Foundation\ErrorHandling;

use Throwable;

interface Reporter
{
    /**
     * Report an error that Forumkit was not able to handle to a backend.
     *
     * @param Throwable $error
     * @return void
     */
    public function report(Throwable $error);
}
