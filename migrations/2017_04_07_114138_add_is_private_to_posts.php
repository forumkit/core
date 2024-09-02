<?php

use Forumkit\Database\Migration;

return Migration::addColumns('posts', [
    'is_private' => ['boolean', 'default' => false]
]);
