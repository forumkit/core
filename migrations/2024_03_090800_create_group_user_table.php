<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'group_user',
    function (Blueprint $table) {
        $table->integer('user_id')->unsigned();
        $table->integer('group_id')->unsigned();
        $table->primary(['user_id', 'group_id']);
    }
);
