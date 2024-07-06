<?php

namespace Forumkit\Settings\Event;

/**
 * 准备设置以在客户端显示。
 *
 * 当从数据库检索设置并准备将其反序列化以在客户端显示时，会触发此事件。
 */
class Deserializing
{
    /**
     * 待反序列化的设置数组。
     *
     * @var array
     */
    public $settings;

    /**
     * @param array $settings 待反序列化的设置数组（使用引用传递）
     */
    public function __construct(&$settings)
    {
        $this->settings = &$settings;
    }
}
