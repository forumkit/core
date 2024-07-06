<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ErrorHandling\Reporter;
use Illuminate\Contracts\Container\Container;

class ErrorHandling implements ExtenderInterface
{
    private $statuses = [];
    private $types = [];
    private $handlers = [];
    private $reporters = [];

    /**
     * 为已知的错误类型定义对应的HTTP状态码。
     *
     * 这可以用于配置在遇到具有特定类型（第一个参数）的异常时使用的状态码（第二个参数）。
     * 这种类型可以由异常类本身提供（如果它实现了 {@see \Forumkit\Foundation\KnownError} 接口），
     * 或者通过使用 {@see type} 方法明确定义（对于不受你控制的异常类很有用）。
     *
     * @param string $errorType: 错误类型
     * @param int $httpStatus: 此错误的状态码
     * @return self
     */
    public function status(string $errorType, int $httpStatus): self
    {
        $this->statuses[$errorType] = $httpStatus;

        return $this;
    }

    /**
     * 为特定的异常类定义内部错误类型。
     *
     * 如果异常类在你的控制之下，
     * 你应该更倾向于让异常实现我们的 {@see \Forumkit\Foundation\KnownError} 接口并在那里定义类型。
     * 此方法仅应用于第三方异常，例如当集成已定义自己异常类的其他包时。
     *
     * @param string $exceptionClass: 异常类的::class属性
     * @param string $errorType: 错误类型
     * @return self
     */
    public function type(string $exceptionClass, string $errorType): self
    {
        $this->types[$exceptionClass] = $errorType;

        return $this;
    }

    /**
     * 注册带有自定义错误处理逻辑的处理程序。
     *
     * 当Forumkit的默认错误处理不足以满足你的需求，并且此扩展器的其他方法也无法提供帮助时，
     * 这是你可以大展拳脚的地方！使用此方法，你可以定义一个处理程序类（第二个参数），
     * 它将负责处理特定类型（第一个参数）的异常。
     *
     * 处理程序类必须实现一个handle()方法（惊喜！），
     * 该方法返回一个 {@see \Forumkit\Foundation\ErrorHandling\HandledError} 实例。
     * 除了通常的类型和HTTP状态码之外，这样的对象还可以包含“详细信息”——有关错误的更多上下文的任意数据。
     *
     * @param string $exceptionClass: 异常类的::class属性
     * @param string $handlerClass: 处理程序类的::class属性
     * @return self
     */
    public function handler(string $exceptionClass, string $handlerClass): self
    {
        $this->handlers[$exceptionClass] = $handlerClass;

        return $this;
    }

    /**
     * 注册一个错误报告器。
     *
     * 当Forumkit遇到它不知道如何处理的异常（即没有与错误类型关联的已知异常）时，将调用报告器。
     * 然后，它们可以将异常写入日志，或将其发送到某些外部服务，以便开发人员和/或管理员收到错误通知。
     *
     * 传入报告器类时，请确保它实现了 {@see \Forumkit\Foundation\ErrorHandling\Reporter} 接口。
     *
     * @param string $reporterClass: 报告器类的::class属性
     * @return self
     */
    public function reporter(string $reporterClass): self
    {
        $this->reporters[] = $reporterClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (count($this->statuses)) {
            $container->extend('forumkit.error.statuses', function ($statuses) {
                return array_merge($statuses, $this->statuses);
            });
        }

        if (count($this->types)) {
            $container->extend('forumkit.error.classes', function ($types) {
                return array_merge($types, $this->types);
            });
        }

        if (count($this->handlers)) {
            $container->extend('forumkit.error.handlers', function ($handlers) {
                return array_merge($handlers, $this->handlers);
            });
        }

        foreach ($this->reporters as $reporterClass) {
            $container->tag($reporterClass, Reporter::class);
        }
    }
}
