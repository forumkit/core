<?php

namespace Forumkit\Database\Exception;

use Exception;

class MigrationKeyMissing extends Exception
{
    protected $direction;

    public function __construct(string $direction, string $file = null)
    {
        $this->direction = $direction;

        $fileNameWithSpace = $file ? ' '.realpath($file) : '';
        parent::__construct("迁移文件$fileNameWithSpace 应包含一个具有up/down属性的数组 (正在寻找 $direction)");
    }

    public function withFile(string $file = null): self
    {
        return new self($this->direction, $file);
    }
}
