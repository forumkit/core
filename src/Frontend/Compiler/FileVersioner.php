<?php

namespace Forumkit\Frontend\Compiler;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class FileVersioner implements VersionerInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;
    const REV_MANIFEST = 'rev-manifest.json';

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function putRevision(string $file, ?string $revision)
    {
        if ($this->filesystem->exists(static::REV_MANIFEST)) {
            $manifest = json_decode($this->filesystem->get(static::REV_MANIFEST), true);
        } else {
            $manifest = [];
        }

        if ($revision) {
            $manifest[$file] = $revision;
        } else {
            unset($manifest[$file]);
        }

        $this->filesystem->put(static::REV_MANIFEST, json_encode($manifest));
    }

    public function getRevision(string $file): ?string
    {
        if ($this->filesystem->exists(static::REV_MANIFEST)) {
            $manifest = json_decode($this->filesystem->get(static::REV_MANIFEST), true);

            return Arr::get($manifest, $file);
        }

        return null;
    }
}
