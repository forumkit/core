<?php

use Forumkit\Database\Migration;

return Migration::renameColumns('users', [
    'is_activated' => 'is_email_confirmed',
    'join_time' => 'joined_at',
    'last_seen_time' => 'last_seen_at',
    'discussions_count' => 'discussion_count',
    'comments_count' => 'comment_count',
    'read_time' => 'marked_all_as_read_at',
    'notifications_read_time' => 'read_notifications_at',
    'avatar_path' => 'avatar_url'
]);
