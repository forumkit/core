<?php

use Forumkit\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'api_keys',
    function (Blueprint $table) {
        $table->string('id', 100)->primary();
    }
);
