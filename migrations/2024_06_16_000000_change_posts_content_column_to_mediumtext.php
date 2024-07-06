<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// https://github.com/doctrine/dbal/issues/2566#issuecomment-480217999
return [
    'up' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->mediumText('content')->comment(' ')->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->text('content')->comment('')->change();
        });
    }
];
