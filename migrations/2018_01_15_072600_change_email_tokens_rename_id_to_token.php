<?php

use Forumkit\Database\Migration;

return Migration::renameColumn('email_tokens', 'id', 'token');
