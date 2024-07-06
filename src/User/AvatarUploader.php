<?php

namespace Forumkit\User;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\Image;

class AvatarUploader
{
    /**
     * @var Filesystem
     */
    protected $uploadDir;

    public function __construct(Factory $filesystemFactory)
    {
        $this->uploadDir = $filesystemFactory->disk('forumkit-avatars');
    }

    /**
     * @param User $user
     * @param Image $image
     */
    public function upload(User $user, Image $image)
    {
        if (extension_loaded('exif')) {
            $image->orientate();
        }

        $encodedImage = $image->fit(100, 100)->encode('png');

        $avatarPath = Str::random().'.png';

        $this->removeFileAfterSave($user);
        $user->changeAvatarPath($avatarPath);

        $this->uploadDir->put($avatarPath, $encodedImage);
    }

    /**
     * 在用户成功保存后处理旧头像文件的删除
     * 我们不在remove()方法中处理这个逻辑，因为如果这样做，在上传头像时会两次调用changeAvatarPath方法。
     * @param User $user
     */
    protected function removeFileAfterSave(User $user)
    {
        $avatarPath = $user->getRawOriginal('avatar_url');

        // 如果没有头像文件，则无需删除
        if (! $avatarPath) {
            return;
        }

        $user->afterSave(function () use ($avatarPath) {
            if ($this->uploadDir->exists($avatarPath)) {
                $this->uploadDir->delete($avatarPath);
            }
        });
    }

    /**
     * @param User $user
     */
    public function remove(User $user)
    {
        $this->removeFileAfterSave($user);

        $user->changeAvatarPath(null);
    }
}
