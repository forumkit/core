<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Formatter\Formatter as ActualFormatter;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Formatter implements ExtenderInterface, LifecycleInterface
{
    private $configurationCallbacks = [];
    private $parsingCallbacks = [];
    private $unparsingCallbacks = [];
    private $renderingCallbacks = [];

    /**
     * 配置格式化器。这可以用于添加对自定义markdown/bbcode/等标签的支持，
     * 或者更改格式化器。请查看s9e文本格式化器库的文档以获取更多关于如何使用此功能的信息。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - \s9e\TextFormatter\Configurator $configurator
     *
     * 可调用函数应返回void
     *
     * @return self
     */
    public function configure($callback): self
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    /**
     * 准备系统进行解析。这可以用于修改要解析的文本或修改解析器。
     * 请注意，无论是否更改，都必须返回要解析的文本。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - \s9e\TextFormatter\Parser $parser
     * - mixed $context
     * - string $text: 要解析的文本
     * - \Forumkit\User\User|null $actor 这个参数必须是可空的，或者完全省略。
     *
     * 回调函数应返回以下值：
     * - string $text: 要解析的文本
     *
     * @return self
     */
    public function parse($callback): self
    {
        $this->parsingCallbacks[] = $callback;

        return $this;
    }

    /**
     * 准备系统进行反解析。这可以用于修改已解析的文本。
     * 请注意，无论是否更改，都必须返回已解析的文本。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - mixed $context
     * - string $xml: 已解析的文本
     *
     * 回调函数应返回以下值：
     * - string $xml: 要反解析的文本
     *
     * @return self
     */
    public function unparse($callback): self
    {
        $this->unparsingCallbacks[] = $callback;

        return $this;
    }

    /**
     * 准备系统进行渲染。这可以用于修改将要渲染的xml或修改渲染器。
     * 请注意，无论是否更改，都必须返回要渲染的xml。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - \s9e\TextFormatter\Renderer $renderer
     * - mixed $context
     * - string $xml: 要渲染的xml
     * - ServerRequestInterface $request 这个参数必须是可空的，或者完全省略。
     *
     * 回调函数应返回以下值：
     * - string $xml: 要渲染的xml
     *
     * @return self
     */
    public function render($callback): self
    {
        $this->renderingCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.formatter', function ($formatter, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                $formatter->addConfigurationCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            foreach ($this->parsingCallbacks as $callback) {
                $formatter->addParsingCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            foreach ($this->unparsingCallbacks as $callback) {
                $formatter->addUnparsingCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            foreach ($this->renderingCallbacks as $callback) {
                $formatter->addRenderingCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            return $formatter;
        });
    }

    public function onEnable(Container $container, Extension $extension)
    {
        // 启用此扩展时对格式化程序缓存进行浮动
        $container->make(ActualFormatter::class)->flush();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        // 禁用此扩展时填充格式化程序缓存
        $container->make(ActualFormatter::class)->flush();
    }
}
