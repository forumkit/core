<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'posts ENGINE = InnoDB');
    },

    'down' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'posts ENGINE = MyISAM');
    }
];
