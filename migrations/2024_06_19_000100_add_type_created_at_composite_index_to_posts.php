<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->index(['type', 'created_at']);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->index(['type', 'created_at']);
        });
    }
];
