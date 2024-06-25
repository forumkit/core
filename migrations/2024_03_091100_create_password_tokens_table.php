<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'password_tokens',
    function (Blueprint $table) {
        $table->string('token', 100)->primary();
        $table->integer('user_id')->unsigned();
        $table->timestamp('created_at');
    }
);
