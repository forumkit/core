<?php

use Forumkit\Database\Migration;

return Migration::addColumns('posts', [
    'ip_address' => ['string', 'length' => 45, 'nullable' => true]
]);
