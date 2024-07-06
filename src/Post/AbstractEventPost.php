<?php

namespace Forumkit\Post;

/**
 * @property array $content
 */
abstract class AbstractEventPost extends Post
{
    /**
     * 从数据库的 JSON 值中反序列化 content 属性。
     *
     * @param string $value
     * @return array
     */
    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * 序列化 content 属性以 JSON 格式存储到数据库中。
     *
     * @param string $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
