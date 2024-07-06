<?php

namespace Forumkit\Foundation;

trait EventGeneratorTrait
{
    /**
     * @var array
     */
    protected $pendingEvents = [];

    /**
     * 触发一个新事件。
     *
     * @param mixed $event
     */
    public function raise($event)
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * 返回并重置所有待处理的事件。
     *
     * @return array
     */
    public function releaseEvents()
    {
        $events = $this->pendingEvents;

        $this->pendingEvents = [];

        return $events;
    }
}
