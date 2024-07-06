<?php

namespace Forumkit\User\Command;

use Forumkit\User\User;
use Psr\Http\Message\UploadedFileInterface;

class UploadAvatar
{
    /**
     * 上传头像的用户ID。
     *
     * @var int
     */
    public $userId;

    /**
     * 要上传的头像文件。
     *
     * @var UploadedFileInterface
     */
    public $file;

    /**
     * 执行操作的用户。
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $userId 上传头像的用户ID
     * @param UploadedFileInterface $file 要上传的头像文件
     * @param User $actor 执行操作的用户
     */
    public function __construct($userId, UploadedFileInterface $file, User $actor)
    {
        $this->userId = $userId;
        $this->file = $file;
        $this->actor = $actor;
    }
}
