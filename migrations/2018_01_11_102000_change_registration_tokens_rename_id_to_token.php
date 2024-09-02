<?php

use Forumkit\Database\Migration;

return Migration::renameColumn('registration_tokens', 'id', 'token');
