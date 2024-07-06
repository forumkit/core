<?php

namespace Forumkit\Foundation;

/**
 * 在 Forumkit 应用程序中具有明确含义的异常。
 *
 * 我们使用这些异常作为从领域层快速上报错误的机制。
 * 通过 {@see \Forumkit\Foundation\ErrorHandling\Registry}
 * 这些异常将被映射到错误“类型”，并在必要时映射到 HTTP 状态。
 * 
 * 实现此接口的异常可以实现自己的逻辑来确定自己的类型（通常是一个硬编码的值）。
 */
interface KnownError
{
    /**
     * 确定异常的类型。
     * 
     * 这应该是一个简短、精确的标识符，用于向用户暴露为错误代码。
     * 此外，它还可以用于在翻译或视图中查找适当的错误消息，以渲染美观的错误页面。
     *
     * 不同的异常类允许返回相同的状态码。
     * 例如，当它们对最终用户具有相似的语义含义，但由不同的子系统抛出时。
     *
     * @return string
     */
    public function getType(): string;
}
