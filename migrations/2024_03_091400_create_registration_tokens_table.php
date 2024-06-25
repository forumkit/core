<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'registration_tokens',
    function (Blueprint $table) {
        $table->string('token', 100)->primary();
        $table->text('payload', 150);
        $table->dateTime('created_at');
        $table->string('provider');
        $table->string('identifier');
        $table->text('user_attributes')->nullable();
    }
);
