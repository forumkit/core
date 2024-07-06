<?php

namespace Forumkit\Post\Access;

use Carbon\Carbon;
use Forumkit\Post\Post;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\Access\AbstractPolicy;
use Forumkit\User\User;

class PostPolicy extends AbstractPolicy
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param \Forumkit\Post\Post $post
     * @return bool|null
     */
    public function can(User $actor, $ability, Post $post)
    {
        if ($actor->can($ability.'Posts', $post->discussion)) {
            return $this->allow();
        }
    }

    /**
     * @param User $actor
     * @param Post $post
     * @return bool|null
     */
    public function edit(User $actor, Post $post)
    {
        // 如果用户是帖子的作者，帖子没有被其他人删除，且用户有权在该讨论中创建新回复，则允许编辑帖子。
        if ($post->user_id == $actor->id && (! $post->hidden_at || $post->hidden_user_id == $actor->id) && $actor->can('reply', $post->discussion)) {
            $allowEditing = $this->settings->get('allow_post_editing');

            if ($allowEditing === '-1'
                || ($allowEditing === 'reply' && $post->number >= $post->discussion->last_post_number)
                || (is_numeric($allowEditing) && $post->created_at->diffInMinutes(new Carbon) < $allowEditing)) {
                return $this->allow();
            }
        }
    }

    /**
     * @param User $actor
     * @param Post $post
     * @return bool|null
     */
    public function hide(User $actor, Post $post)
    {
        if ($post->user_id == $actor->id && (! $post->hidden_at || $post->hidden_user_id == $actor->id) && $actor->can('reply', $post->discussion)) {
            $allowHiding = $this->settings->get('allow_hide_own_posts');

            if ($allowHiding === '-1'
                || ($allowHiding === 'reply' && $post->number >= $post->discussion->last_post_number)
                || (is_numeric($allowHiding) && $post->created_at->diffInMinutes(new Carbon) < $allowHiding)) {
                return $this->allow();
            }
        }
    }
}
