<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// 我们需要一个完整的自定义迁移，因为在创建表之后，我们需要使用原始 SQL 语句为内容添加全文索引。
return [
    'up' => function (Builder $schema) {
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('discussion_id')->unsigned();
            $table->integer('number')->unsigned()->nullable();

            $table->dateTime('created_at');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('type', 100)->nullable();
            $table->text('content')->nullable();

            $table->dateTime('edited_at')->nullable();
            $table->integer('edited_user_id')->unsigned()->nullable();
            $table->dateTime('hidden_at')->nullable();
            $table->integer('hidden_user_id')->unsigned()->nullable();

            $table->unique(['discussion_id', 'number']);

            //添加
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_private')->default(0);


        });

        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'posts ADD FULLTEXT content (content)');
    },

    'down' => function (Builder $schema) {
        $schema->drop('posts');
    }
];
