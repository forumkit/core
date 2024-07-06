<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Console implements ExtenderInterface
{
    protected $addCommands = [];
    protected $scheduled = [];

    /**
     * 向控制台添加一个命令。
     *
     * @param string $command: 命令类的::class属性，该类必须扩展 Forumkit\Console\AbstractCommand.
     * @return self
     */
    public function command(string $command): self
    {
        $this->addCommands[] = $command;

        return $this;
    }

    /**
     * 计划一个命令在指定间隔内运行。
     *
     * @param string $command: 命令类的::class属性，该类必须扩展 Forumkit\Console\AbstractCommand.
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - \Illuminate\Console\Scheduling\Event $event
     *
     * 回调函数应对$event应用相关方法，并且不需要返回任何内容。
     *
     * @see https://laravel.com/api/8.x/Illuminate/Console/Scheduling/Event.html
     * @see https://laravel.com/docs/8.x/scheduling#schedule-frequency-options
     * 了解更多可用的方法及其功能。
     *
     * @param array $args 调用命令时传递的参数数组
     * @return self
     */
    public function schedule(string $command, $callback, $args = []): self
    {
        $this->scheduled[] = compact('args', 'callback', 'command');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.console.commands', function ($existingCommands) {
            return array_merge($existingCommands, $this->addCommands);
        });

        $container->extend('forumkit.console.scheduled', function ($existingScheduled) use ($container) {
            foreach ($this->scheduled as &$schedule) {
                $schedule['callback'] = ContainerUtil::wrapCallback($schedule['callback'], $container);
            }

            return array_merge($existingScheduled, $this->scheduled);
        });
    }
}
