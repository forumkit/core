<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')
            ->where('permission', 'LIKE', '%viewDiscussions')
            ->update(['permission' => $db->raw("REPLACE(permission,  'viewDiscussions', 'viewForum')")]);

        $db->table('group_permission')
            ->where('permission', 'viewUserList')
            ->update(['permission' => 'searchUsers']);
    },

    'down' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')
            ->where('permission', 'LIKE', '%viewForum')
            ->update(['permission' => $db->raw("REPLACE(permission,  'viewForum', 'viewDiscussions')")]);

        $db->table('group_permission')
            ->where('permission', 'searchUsers')
            ->update(['permission' => 'viewUserList']);
    }
];
