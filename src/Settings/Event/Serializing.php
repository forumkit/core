<?php

namespace Forumkit\Settings\Event;

class Serializing
{
    /**
     * 要保存的设置键。
     *
     * @var string
     */
    public $key;

    /**
     * 要保存的设置值。
     *
     * @var string
     */
    public $value;

    /**
     * @param string $key 要保存的设置键
     * @param string $value 要保存的设置值（使用引用传递）
     */
    public function __construct($key, &$value)
    {
        $this->key = $key;
        $this->value = &$value;
    }
}
