<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'config',
    function (Blueprint $table) {
        $table->string('key', 100)->primary();
        $table->binary('value')->nullable();
    }
);
