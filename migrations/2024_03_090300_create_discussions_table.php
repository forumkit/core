<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'discussions',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('title', 200);
        $table->integer('comment_count')->unsigned()->default(1);
        $table->integer('participant_count')->unsigned()->default(0);
        $table->integer('post_number_index')->unsigned()->default(0);

        $table->dateTime('created_at');
        $table->integer('user_id')->unsigned()->nullable();
        $table->integer('first_post_id')->unsigned()->nullable();

        $table->dateTime('last_posted_at')->nullable();
        $table->integer('last_posted_user_id')->unsigned()->nullable();
        $table->integer('last_post_id')->unsigned()->nullable();
        $table->integer('last_post_number')->unsigned()->nullable();

        //添加
        $table->dateTime('hidden_at')->nullable();
        $table->integer('hidden_user_id')->unsigned()->nullable();

        $table->string('slug')->nullable();
        $table->boolean('is_private')->default(0);
    }
);
