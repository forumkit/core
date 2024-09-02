<?php

use Forumkit\Database\Migration;

return Migration::renameColumn('password_tokens', 'id', 'token');
