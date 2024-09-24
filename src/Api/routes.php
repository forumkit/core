<?php

use Forumkit\Api\Controller;
use Forumkit\Http\RouteCollection;
use Forumkit\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    // 获取论坛信息
    $map->get(
        '/',
        'forum.show',
        $route->toController(Controller\ShowForumController::class)
    );

    // 列出访问令牌
    $map->get(
        '/access-tokens',
        'access-tokens.index',
        $route->toController(Controller\ListAccessTokensController::class)
    );

    // 创建访问令牌
    $map->post(
        '/access-tokens',
        'access-tokens.create',
        $route->toController(Controller\CreateAccessTokenController::class)
    );

    // 删除指定ID的访问令牌
    $map->delete(
        '/access-tokens/{id}',
        'access-tokens.delete',
        $route->toController(Controller\DeleteAccessTokenController::class)
    );

    // 获取认证令牌
    $map->post(
        '/token',
        'token',
        $route->toController(Controller\CreateTokenController::class)
    );

    // 终止所有其他会话
    $map->delete(
        '/sessions',
        'sessions.delete',
        $route->toController(Controller\TerminateAllOtherSessionsController::class)
    );

    // 发送忘记密码邮件
    $map->post(
        '/forgot',
        'forgot',
        $route->toController(Controller\ForgotPasswordController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | 用户
    |--------------------------------------------------------------------------
    */

    // 列出用户
    $map->get(
        '/users',
        'users.index',
        $route->toController(Controller\ListUsersController::class)
    );

    // 注册用户
    $map->post(
        '/users',
        'users.create',
        $route->toController(Controller\CreateUserController::class)
    );

    // 获取单个用户
    $map->get(
        '/users/{id}',
        'users.show',
        $route->toController(Controller\ShowUserController::class)
    );

    // 编辑用户
    $map->patch(
        '/users/{id}',
        'users.update',
        $route->toController(Controller\UpdateUserController::class)
    );

    // 删除用户
    $map->delete(
        '/users/{id}',
        'users.delete',
        $route->toController(Controller\DeleteUserController::class)
    );

    // 上传头像
    $map->post(
        '/users/{id}/avatar',
        'users.avatar.upload',
        $route->toController(Controller\UploadAvatarController::class)
    );

    // 删除头像
    $map->delete(
        '/users/{id}/avatar',
        'users.avatar.delete',
        $route->toController(Controller\DeleteAvatarController::class)
    );

    // 发送确认邮件
    $map->post(
        '/users/{id}/send-confirmation',
        'users.confirmation.send',
        $route->toController(Controller\SendConfirmationEmailController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | 通知
    |--------------------------------------------------------------------------
    */

    // 列出当前用户的通知
    $map->get(
        '/notifications',
        'notifications.index',
        $route->toController(Controller\ListNotificationsController::class)
    );

    // 标记所有通知为已读
    $map->post(
        '/notifications/read',
        'notifications.readAll',
        $route->toController(Controller\ReadAllNotificationsController::class)
    );

    // 标记单个通知为已读
    $map->patch(
        '/notifications/{id}',
        'notifications.update',
        $route->toController(Controller\UpdateNotificationController::class)
    );

    // 删除当前用户的所有通知
    $map->delete(
        '/notifications',
        'notifications.deleteAll',
        $route->toController(Controller\DeleteAllNotificationsController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | 讨论区
    |--------------------------------------------------------------------------
    */

    // 列出所有讨论
    $map->get(
        '/discussions',
        'discussions.index',
        $route->toController(Controller\ListDiscussionsController::class)
    );

    // 创建一个新讨论
    $map->post(
        '/discussions',
        'discussions.create',
        $route->toController(Controller\CreateDiscussionController::class)
    );

    // 显示单个讨论
    $map->get(
        '/discussions/{id}',
        'discussions.show',
        $route->toController(Controller\ShowDiscussionController::class)
    );

    // 编辑讨论
    $map->patch(
        '/discussions/{id}',
        'discussions.update',
        $route->toController(Controller\UpdateDiscussionController::class)
    );

    // 删除讨论
    $map->delete(
        '/discussions/{id}',
        'discussions.delete',
        $route->toController(Controller\DeleteDiscussionController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | 帖子
    |--------------------------------------------------------------------------
    */

    // 列出帖子，通常用于讨论区
    $map->get(
        '/posts',
        'posts.index',
        $route->toController(Controller\ListPostsController::class)
    );

    // 创建一个新帖子
    $map->post(
        '/posts',
        'posts.create',
        $route->toController(Controller\CreatePostController::class)
    );

    // 显示单个或多个帖子
    $map->get(
        '/posts/{id}',
        'posts.show',
        $route->toController(Controller\ShowPostController::class)
    );

    // 编辑帖子
    $map->patch(
        '/posts/{id}',
        'posts.update',
        $route->toController(Controller\UpdatePostController::class)
    );

    // 删除帖子
    $map->delete(
        '/posts/{id}',
        'posts.delete',
        $route->toController(Controller\DeletePostController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | 用户组
    |--------------------------------------------------------------------------
    */

    // 列出所有用户组
    $map->get(
        '/groups',
        'groups.index',
        $route->toController(Controller\ListGroupsController::class)
    );

    // 创建一个新用户组
    $map->post(
        '/groups',
        'groups.create',
        $route->toController(Controller\CreateGroupController::class)
    );

    // 显示单个用户组
    $map->get(
        '/groups/{id}',
        'groups.show',
        $route->toController(Controller\ShowGroupController::class)
    );

    // 编辑用户组
    $map->patch(
        '/groups/{id}',
        'groups.update',
        $route->toController(Controller\UpdateGroupController::class)
    );

    // 删除用户组
    $map->delete(
        '/groups/{id}',
        'groups.delete',
        $route->toController(Controller\DeleteGroupController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | 管理员操作
    |--------------------------------------------------------------------------
    */

    // 切换扩展的启用/禁用状态
    $map->patch(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(Controller\UpdateExtensionController::class)
    );

    // 卸载扩展
    $map->delete(
        '/extensions/{name}',
        'extensions.delete',
        $route->toController(Controller\UninstallExtensionController::class)
    );

    // 获取扩展的README文件
    $map->get(
        '/extension-readmes/{name}',
        'extension-readmes.show',
        $route->toController(Controller\ShowExtensionReadmeController::class)
    );

    // 更新设置
    $map->post(
        '/settings',
        'settings',
        $route->toController(Controller\SetSettingsController::class)
    );

    // 更新权限
    $map->post(
        '/permission',
        'permission',
        $route->toController(Controller\SetPermissionController::class)
    );

    // 上传网站Logo
    $map->post(
        '/logo',
        'logo',
        $route->toController(Controller\UploadLogoController::class)
    );

    // 删除网站Logo
    $map->delete(
        '/logo',
        'logo.delete',
        $route->toController(Controller\DeleteLogoController::class)
    );

    // 上传网站Favicon
    $map->post(
        '/favicon',
        'favicon',
        $route->toController(Controller\UploadFaviconController::class)
    );

    // 删除网站Favicon
    $map->delete(
        '/favicon',
        'favicon.delete',
        $route->toController(Controller\DeleteFaviconController::class)
    );

    // 清除缓存
    $map->delete(
        '/cache',
        'cache.clear',
        $route->toController(Controller\ClearCacheController::class)
    );

    // 列出可用的邮件驱动、可用字段和验证状态
    $map->get(
        '/mail/settings',
        'mailSettings.index',
        $route->toController(Controller\ShowMailSettingsController::class)
    );

    // 发送测试邮件
    $map->post(
        '/mail/test',
        'mailTest',
        $route->toController(Controller\SendTestMailController::class)
    );
};
