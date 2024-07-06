<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'discussions ADD FULLTEXT title (title)');
    },

    'down' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'discussions DROP INDEX title');
    }
];
