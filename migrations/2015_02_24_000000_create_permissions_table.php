<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'permissions',
    function (Blueprint $table) {
        $table->integer('group_id')->unsigned();
        $table->string('permission', 100);
        $table->primary(['group_id', 'permission']);
    }
);
