<?php

namespace Forumkit\Install\Prerequisite;

use Illuminate\Support\Collection;

interface PrerequisiteInterface
{
    /**
     * Verify that this prerequisite is fulfilled.
     *
     * If everything is okay, this method should return an empty Collection
     * instance. When problems are detected, it should return a Collection of
     * arrays, each having at least a "message" and optionally a "detail" key.
     *
     * @return Collection
     */
    public function problems(): Collection;
}
