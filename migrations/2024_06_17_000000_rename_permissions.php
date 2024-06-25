<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')
            ->where('permission', 'LIKE', '%viewDiscussions')
            ->update(['permission' => $db->raw("REPLACE(permission,  'viewDiscussions', 'viewSite')")]);

        $db->table('group_permission')
            ->where('permission', 'viewUserList')
            ->update(['permission' => 'searchUsers']);
    },

    'down' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')
            ->where('permission', 'LIKE', '%viewSite')
            ->update(['permission' => $db->raw("REPLACE(permission,  'viewSite', 'viewDiscussions')")]);

        $db->table('group_permission')
            ->where('permission', 'searchUsers')
            ->update(['permission' => 'viewUserList']);
    }
];
