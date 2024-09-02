<?php

use Forumkit\Database\Migration;

return Migration::renameColumns('discussion_user', [
    'read_time' => 'last_read_at',
    'read_number' => 'last_read_post_number'
]);
