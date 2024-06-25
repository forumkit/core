<?php

namespace Forumkit\Install\Prerequisite;

use Illuminate\Support\Collection;

class PhpVersion implements PrerequisiteInterface
{
    protected $minVersion;

    public function __construct($minVersion)
    {
        $this->minVersion = $minVersion;
    }

    public function problems(): Collection
    {
        $collection = new Collection;

        if (version_compare(PHP_VERSION, $this->minVersion, '<')) {
            $collection->push([
                'message' => "PHP $this->minVersion 是必需的。",
                'detail' => '您正在运行版本 '.PHP_VERSION.' 您可能需要与系统管理员讨论升级到最新的 PHP 版本。',
            ]);
        }

        return $collection;
    }
}
