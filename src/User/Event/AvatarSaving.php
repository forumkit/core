<?php

namespace Forumkit\User\Event;

use Forumkit\User\User;
use Intervention\Image\Image;

class AvatarSaving
{
    /**
     * 头像将被保存的用户。
     *
     * @var User
     */
    public $user;

    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * 将被保存的图像。
     *
     * @var Image
     */
    public $image;

    /**
     * @param User $user 头像将被保存的用户
     * @param User $actor 执行操作的用户
     * @param Image $image 将被保存的图像
     */
    public function __construct(User $user, User $actor, Image $image)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->image = $image;
    }
}
