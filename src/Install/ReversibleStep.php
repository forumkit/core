<?php

namespace Forumkit\Install;

interface ReversibleStep extends Step
{
    public function revert();
}
