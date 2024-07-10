<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'page',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('title', 200);
        $table->string('slug', 200);
        $table->dateTime('time');
        $table->dateTime('edit_time')->nullable();
        $table->mediumText('content')->nullable();
        $table->boolean('is_hidden')->default(0);
        $table->boolean('is_html')->default(0);
        $table->boolean('is_restricted')->default(0);
    }

);
