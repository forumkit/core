<?php

use Forumkit\Admin\Content\Index;
use Forumkit\Admin\Controller\UpdateExtensionController;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    $map->get(
        '/',
        'index',
        $route->toAdmin(Index::class)
    );

    $map->post(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(UpdateExtensionController::class)
    );
};
