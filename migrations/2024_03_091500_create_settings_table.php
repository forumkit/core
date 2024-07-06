<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

// 创建表
return Migration::createTable(
    'settings',
    function (Blueprint $table) {
        $table->string('key', 100)->primary();
        $table->text('value')->nullable();
    }
);
