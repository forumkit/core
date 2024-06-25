<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'discussion_user',
    function (Blueprint $table) {
        $table->integer('user_id')->unsigned();
        $table->integer('discussion_id')->unsigned();
        $table->dateTime('last_read_at')->nullable();
        $table->integer('last_read_post_number')->unsigned()->nullable();
        $table->primary(['user_id', 'discussion_id']);
    }
);
