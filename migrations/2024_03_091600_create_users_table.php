<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'users',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('username', 100)->unique();
        $table->string('email', 150)->unique();
        $table->boolean('is_email_confirmed')->default(0);
        $table->string('password', 100);
        $table->string('avatar_url', 100)->nullable();
        $table->binary('preferences')->nullable();
        $table->dateTime('joined_at')->nullable();
        $table->dateTime('last_seen_at')->nullable();
        $table->dateTime('marked_all_as_read_at')->nullable();
        $table->dateTime('read_notifications_at')->nullable();
        $table->integer('discussion_count')->unsigned()->default(0);
        $table->integer('comment_count')->unsigned()->default(0);
    }
);
