<?php

namespace Forumkit\Frontend\Compiler;

interface CompilerInterface
{
    public function getFilename(): string;

    public function setFilename(string $filename);

    public function addSources(callable $callback);

    public function commit(bool $force = false);

    public function getUrl(): ?string;

    public function flush();
}
