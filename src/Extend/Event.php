<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class Event implements ExtenderInterface
{
    private $listeners = [];
    private $subscribers = [];

    /**
     * 为由forumkit或其扩展派发的领域事件添加一个监听器。
     *
     * @param string $event: 事件的名称，可以是事件类的::class属性。
     * @param callable|string $listener
     *
     * 监听器可以是：
     *  - 一个回调函数，该函数接受事件实例作为参数。
     *  - 一个带有公共`handle`方法的类的::class属性，该方法接受事件实例作为参数。
     *  - 一个数组，其中第一个参数是对象或类名，第二个参数是应在第一个参数上执行的作为监听器的方法。
     *
     * @return self
     */
    public function listen(string $event, $listener): self
    {
        $this->listeners[] = [$event, $listener];

        return $this;
    }

    /**
     * 为由forumkit或其扩展派发的一组领域事件添加一个订阅者。
     * 事件订阅者是类，可以在订阅者类本身内部订阅多个事件，
     * 允许你在单个类中定义多个事件处理程序。
     *
     * @see https://laravel.com/docs/8.x/events#writing-event-subscribers
     *
     * @param string $subscriber: 订阅者类的::class属性。
     * @return self
     */
    public function subscribe(string $subscriber): self
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $events = $container->make(Dispatcher::class);

        $app = $container->make('forumkit');

        $app->booted(function () use ($events) {
            foreach ($this->listeners as $listener) {
                $events->listen($listener[0], $listener[1]);
            }

            foreach ($this->subscribers as $subscriber) {
                $events->subscribe($subscriber);
            }
        });
    }
}
