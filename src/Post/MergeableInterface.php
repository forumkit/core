<?php

namespace Forumkit\Post;

/**
 * 一个帖子，具有与相邻帖子合并的能力。
 *
 * 这种功能仅由某些类型的帖子实现。例如，
 * 如果一个“讨论重命名”的帖子紧接着另一个“讨论重命名”的帖子发布，
 * 那么新的帖子将被合并到旧的帖子中。
 */
interface MergeableInterface
{
    /**
     * 保存模型，假设它将紧接在传递的模型之后出现。
     *
     * @param \Forumkit\Post\Post|null $previous 之前的帖子（可为空）
     * @return static T合并后的模型。如果合并不成功，应该是当前的模型实例。否则，它应该是被合并到的模型
     */
    public function saveAfter(Post $previous = null);
}
