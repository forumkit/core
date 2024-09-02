<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'access_tokens',
    function (Blueprint $table) {
        $table->string('id', 100)->primary();
        $table->integer('user_id')->unsigned();
        $table->timestamp('created_at');
        $table->timestamp('expires_at');
    }
);
