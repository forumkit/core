<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\CurrentUserSerializer;
use Forumkit\Api\Serializer\UserSerializer;
use Forumkit\Http\RequestUtil;
use Forumkit\Http\SlugManager;
use Forumkit\User\User;
use Forumkit\User\UserRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowUserController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = UserSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param SlugManager $slugManager
     * @param UserRepository $users
     */
    public function __construct(SlugManager $slugManager, UserRepository $users)
    {
        $this->slugManager = $slugManager;
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        if (Arr::get($request->getQueryParams(), 'bySlug', false)) {
            $user = $this->slugManager->forResource(User::class)->fromSlug($id, $actor);
        } else {
            $user = $this->users->findOrFail($id, $actor);
        }

        if ($actor->id === $user->id) {
            $this->serializer = CurrentUserSerializer::class;
        }

        return $user;
    }
}
