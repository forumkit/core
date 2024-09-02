<?php

use Forumkit\Database\Migration;

return Migration::addColumns('discussions', [
    'hide_time' => ['dateTime', 'nullable' => true],
    'hide_user_id' => ['integer', 'unsigned' => true, 'nullable' => true]
]);
