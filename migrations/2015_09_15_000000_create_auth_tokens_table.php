<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'auth_tokens',
    function (Blueprint $table) {
        $table->string('id', 100)->primary();
        $table->string('payload', 150);
        $table->timestamp('created_at');
    }
);
