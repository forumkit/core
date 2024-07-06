<?php

namespace Forumkit\Post;

use Carbon\Carbon;

/**
 * 一个帖子，表示讨论的标题已更改。
 *
 * 内容被存储为一个顺序数组，包含旧标题和新标题。
 */
class DiscussionRenamedPost extends AbstractEventPost implements MergeableInterface
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'discussionRenamed';

    /**
     * {@inheritdoc}
     */
    public function saveAfter(Post $previous = null)
    {
        // 如果前一个帖子是另一个'讨论重命名'帖子，并且是由同一用户发布的，
        // 那么我们可以将这个帖子合并到前一个帖子中。
        // 如果发现我们实际上已经还原了标题，就删除这个帖子。
        // 否则，更新其内容。
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content[0] == $this->content[1]) {
                $previous->delete();
            } else {
                $previous->content = static::buildContent($previous->content[0], $this->content[1]);

                $previous->save();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    /**
     * 创建一个针对讨论的回复实例。
     *
     * @param int $discussionId 讨论ID
     * @param int $userId 用户ID
     * @param string $oldTitle 旧标题
     * @param string $newTitle 新标题
     * @return static
     */
    public static function reply($discussionId, $userId, $oldTitle, $newTitle)
    {
        $post = new static;

        $post->content = static::buildContent($oldTitle, $newTitle);
        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }

    /**
     * 构建内容属性。
     *
     * @param string $oldTitle 讨论的旧标题
     * @param string $newTitle 讨论的新标题
     * @return array
     */
    protected static function buildContent($oldTitle, $newTitle)
    {
        return [$oldTitle, $newTitle];
    }
}
