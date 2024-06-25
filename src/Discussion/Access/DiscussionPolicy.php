<?php

namespace Forumkit\Discussion\Access;

use Forumkit\Discussion\Discussion;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\Access\AbstractPolicy;
use Forumkit\User\User;

class DiscussionPolicy extends AbstractPolicy
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @param string $ability
     * @return string|void
     */
    public function can(User $actor, $ability)
    {
        if ($actor->hasPermission('discussion.'.$ability)) {
            return $this->allow();
        }
    }

    /**
     * @param User $actor
     * @param \Forumkit\Discussion\Discussion $discussion
     * @return bool|null
     */
    public function rename(User $actor, Discussion $discussion)
    {
        if ($discussion->user_id == $actor->id && $actor->can('reply', $discussion)) {
            $allowRenaming = $this->settings->get('allow_renaming');

            if ($allowRenaming === '-1'
                || ($allowRenaming === 'reply' && $discussion->participant_count <= 1)
                || (is_numeric($allowRenaming) && $discussion->created_at->diffInMinutes() < $allowRenaming)) {
                return $this->allow();
            }
        }
    }

    /**
     * @param User $actor
     * @param \Forumkit\Discussion\Discussion $discussion
     * @return bool|null
     */
    public function hide(User $actor, Discussion $discussion)
    {
        if ($discussion->user_id == $actor->id
            && $discussion->participant_count <= 1
            && (! $discussion->hidden_at || $discussion->hidden_user_id == $actor->id)
            && $actor->can('reply', $discussion)
        ) {
            return $this->allow();
        }
    }
}
