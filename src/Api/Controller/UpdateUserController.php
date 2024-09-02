<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\CurrentUserSerializer;
use Forumkit\Api\Serializer\UserSerializer;
use Forumkit\Http\RequestUtil;
use Forumkit\User\Command\EditUser;
use Forumkit\User\Exception\NotAuthenticatedException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateUserController extends AbstractShowController
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
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'data', []);

        if ($actor->id == $id) {
            $this->serializer = CurrentUserSerializer::class;
        }

        // Require the user's current password if they are attempting to change
        // their own email address.
        if (isset($data['attributes']['email']) && $actor->id == $id) {
            $password = (string) Arr::get($request->getParsedBody(), 'meta.password');

            if (! $actor->checkPassword($password)) {
                throw new NotAuthenticatedException;
            }
        }

        return $this->bus->dispatch(
            new EditUser($id, $actor, $data)
        );
    }
}
