<?php

namespace Forumkit\Install\Prerequisite;

use Illuminate\Support\Collection;

interface PrerequisiteInterface
{
    /**
     * 验证这个先决条件是否满足。
     *
     * 如果一切正常，该方法应该返回一个空的 Collection 实例。
     * 当检测到问题时，它应该返回一个数组的 Collection，其中每个数组至少包含一个 "message" 键，并且可选地包含一个 "detail" 键。
     *
     * @return Collection
     */
    public function problems(): Collection;
}
