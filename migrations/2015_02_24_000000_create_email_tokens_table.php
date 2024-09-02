<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'email_tokens',
    function (Blueprint $table) {
        $table->string('id', 100)->primary();
        $table->string('email', 150);
        $table->integer('user_id')->unsigned();
        $table->timestamp('created_at');
    }
);
