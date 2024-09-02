<?php

namespace Forumkit\User\Command;

use Forumkit\Foundation\DispatchEventsTrait;
use Forumkit\User\AvatarUploader;
use Forumkit\User\AvatarValidator;
use Forumkit\User\Event\AvatarSaving;
use Forumkit\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Intervention\Image\ImageManager;

class UploadAvatarHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Forumkit\User\UserRepository
     */
    protected $users;

    /**
     * @var AvatarUploader
     */
    protected $uploader;

    /**
     * @var \Forumkit\User\AvatarValidator
     */
    protected $validator;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param AvatarUploader $uploader
     * @param AvatarValidator $validator
     */
    public function __construct(Dispatcher $events, UserRepository $users, AvatarUploader $uploader, AvatarValidator $validator, ImageManager $imageManager)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploader = $uploader;
        $this->validator = $validator;
        $this->imageManager = $imageManager;
    }

    /**
     * @param UploadAvatar $command
     * @return \Forumkit\User\User
     * @throws \Forumkit\User\Exception\PermissionDeniedException
     * @throws \Forumkit\Foundation\ValidationException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        $actor->assertCan('uploadAvatar', $user);

        $this->validator->assertValid(['avatar' => $command->file]);

        $image = $this->imageManager->make($command->file->getStream()->getMetadata('uri'));

        $this->events->dispatch(
            new AvatarSaving($user, $actor, $image)
        );

        $this->uploader->upload($user, $image);

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
