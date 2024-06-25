<?php

namespace Forumkit\Api\Serializer;

class CurrentUserSerializer extends UserSerializer
{
    /**
     * @param \Forumkit\User\User $user
     * @return array
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        $attributes += [
            'isEmailConfirmed'         => (bool) $user->is_email_confirmed,
            'email'                    => $user->email,
            'markedAllAsReadAt'        => $this->formatDate($user->marked_all_as_read_at),
            'unreadNotificationCount'  => (int) $user->getUnreadNotificationCount(),
            'newNotificationCount'     => (int) $user->getNewNotificationCount(),
            'preferences'              => (array) $user->preferences,
            'isAdmin'                  => $user->isAdmin(),
        ];

        return $attributes;
    }
}
