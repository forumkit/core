<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dateTime('last_activity_at')->nullable()->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            // 将 last_activity_at 设置为非空是不可能的，因为这会搞乱现有的数据。
        });
    }
];
