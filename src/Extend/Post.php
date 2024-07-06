<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Post\Post as PostModel;
use Illuminate\Contracts\Container\Container;

class Post implements ExtenderInterface
{
    private $postTypes = [];

    /**
     * 注册一个新的帖子类型。这通常用于自定义的“事件帖子”，
     * 例如当讨论被重命名时出现的帖子。
     *
     * @param string $postType: 要添加的自定义帖子类型的::class属性
     * @return self
     */
    public function type(string $postType): self
    {
        $this->postTypes[] = $postType;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->postTypes as $postType) {
            PostModel::setModel($postType::$type, $postType);
        }
    }
}
