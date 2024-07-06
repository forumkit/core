<?php

namespace Forumkit\Post;

use Carbon\Carbon;
use Forumkit\Formatter\Formatter;
use Forumkit\Post\Event\Hidden;
use Forumkit\Post\Event\Posted;
use Forumkit\Post\Event\Restored;
use Forumkit\Post\Event\Revised;
use Forumkit\User\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 讨论中的一个标准评论。
 *
 * @property string $parsed_content 解析后的内容
 */
class CommentPost extends Post
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'comment';

    /**
     * 文本格式化器实例。
     *
     * @var \Forumkit\Formatter\Formatter
     */
    protected static $formatter;

    /**
     * 创建一个新的实例以回复讨论。
     *
     * @param int $discussionId 讨论ID
     * @param string $content 内容
     * @param int $userId 用户ID
     * @param string $ipAddress IP地址
     * @param User|null $actor 执行者（用户）（可为空）
     * @return static
     */
    public static function reply($discussionId, $content, $userId, $ipAddress, User $actor = null)
    {
        $post = new static;

        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;
        $post->type = static::$type;
        $post->ip_address = $ipAddress;

        // 最后设置内容，因为解析可能依赖于其他帖子属性。
        $post->setContentAttribute($content, $actor);

        $post->raise(new Posted($post));

        return $post;
    }

    /**
     * 修改帖子的内容。
     *
     * @param string $content 内容
     * @param User $actor 执行者（用户）
     * @return $this 返回当前实例
     */
    public function revise($content, User $actor)
    {
        if ($this->content !== $content) {
            $oldContent = $this->content;

            $this->setContentAttribute($content, $actor);

            $this->edited_at = Carbon::now();
            $this->edited_user_id = $actor->id;

            $this->raise(new Revised($this, $actor, $oldContent));
        }

        return $this;
    }

    /**
     * 隐藏帖子。
     *
     * @param User $actor
     * @return $this
     */
    public function hide(User $actor = null)
    {
        if (! $this->hidden_at) {
            $this->hidden_at = Carbon::now();
            $this->hidden_user_id = $actor ? $actor->id : null;

            $this->raise(new Hidden($this));
        }

        return $this;
    }

    /**
     * 恢复帖子。
     *
     * @return $this
     */
    public function restore()
    {
        if ($this->hidden_at !== null) {
            $this->hidden_at = null;
            $this->hidden_user_id = null;

            $this->raise(new Restored($this));
        }

        return $this;
    }

    /**
     * 取消解析已解析的内容。
     *
     * @param string $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return static::$formatter->unparse($value, $this);
    }

    /**
     * 获取解析/原始内容。
     *
     * @return string
     */
    public function getParsedContentAttribute()
    {
        return $this->attributes['content'];
    }

    /**
     * 在保存到数据库之前解析内容。
     *
     * @param string $value
     * @param User $actor
     */
    public function setContentAttribute($value, User $actor = null)
    {
        $this->attributes['content'] = $value ? static::$formatter->parse($value, $this, $actor ?? $this->user) : null;
    }

    /**
     * 设置解析/原始内容。
     *
     * @param string $value
     */
    public function setParsedContentAttribute($value)
    {
        $this->attributes['content'] = $value;
    }

    /**
     * 将内容渲染为HTML。
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function formatContent(ServerRequestInterface $request = null)
    {
        return static::$formatter->render($this->attributes['content'], $this, $request);
    }

    /**
     * 获取文本格式化器实例。
     *
     * @return \Forumkit\Formatter\Formatter
     */
    public static function getFormatter()
    {
        return static::$formatter;
    }

    /**
     * 设置文本格式化器实例。
     *
     * @param \Forumkit\Formatter\Formatter $formatter
     */
    public static function setFormatter(Formatter $formatter)
    {
        static::$formatter = $formatter;
    }
}
