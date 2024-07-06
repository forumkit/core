<?php

namespace Forumkit\Queue;

use Illuminate\Contracts\Queue\Factory;

class QueueFactory implements Factory
{
    /**
     * @var callable
     */
    private $factory;

    /**
     * 缓存的队列实例。
     *
     * @var \Illuminate\Contracts\Queue\Queue|null
     */
    private $queue;

    /**
     * QueueFactory 构造函数。
     *
     * 期望一个回调函数，该回调函数将在应用程序请求时用于实例化队列适配器。
     *
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * 解析队列连接实例。
     *
     * @param string $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null)
    {
        if (is_null($this->queue)) {
            $this->queue = ($this->factory)();
        }

        return $this->queue;
    }
}
