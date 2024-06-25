<?php

namespace Forumkit\Frontend\Compiler\Source;

interface SourceInterface
{
    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @return mixed
     */
    public function getCacheDifferentiator();
}
