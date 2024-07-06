<?php

namespace Forumkit\Mail;

use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Swift_Transport;

/**
 * 邮件服务的接口。
 *
 * 此接口为在Forumkit中配置、检查和使用Laravel的各种电子邮件驱动程序提供了所有必要的方法。
 *
 * @public
 */
interface DriverInterface
{
    /**
     * 提供此驱动程序的设置列表。
     *
     * 列表必须是一个字段名（键）映射到它们类型（对于文本字段使用空字符串""；对于下拉字段，使用可能值的数组）的数组。
     */
    public function availableSettings(): array;

    /**
     * 确保给定的设置足够发送电子邮件。
     *
     * 此方法负责确定存储在Forumkit设置中的用户提供的值是否“有效”，这可以通过对这些值的简单检查来确定。
     * 当然，这并不意味着邮件服务器或API将实际接受例如凭据。
     *
     * 任何错误都必须包装在 {@see \Illuminate\Support\MessageBag} 中。
     * 如果没有错误，可以返回一个空实例。
     * 在配置的邮件驱动程序存在验证问题的情况下，Forumkit将回退到其无操作的 {@see \Forumkit\Mail\NullDriver} 。
     */
    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag;

    /**
     * 这个驱动程序是否实际发送电子邮件？
     */
    public function canSend(): bool;

    /**
     * 根据Forumkit的当前设置构建邮件传输。
     */
    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport;
}
