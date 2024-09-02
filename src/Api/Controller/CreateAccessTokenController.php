<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\AccessTokenSerializer;
use Forumkit\Http\DeveloperAccessToken;
use Forumkit\Http\Event\DeveloperTokenCreated;
use Forumkit\Http\RequestUtil;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

/**
 * Not to be confused with the CreateTokenController,
 * this controller is used by the actor to manually create a developer type access token.
 */
class CreateAccessTokenController extends AbstractCreateController
{
    public $serializer = AccessTokenSerializer::class;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Factory
     */
    protected $validation;

    public function __construct(Dispatcher $events, Factory $validation)
    {
        $this->events = $events;
        $this->validation = $validation;
    }

    /**
     * {@inheritdoc}
     */
    public function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();
        $actor->assertCan('createAccessToken');

        $title = Arr::get($request->getParsedBody(), 'data.attributes.title');

        $this->validation->make(compact('title'), [
            'title' => 'required|string|max:255',
        ])->validate();

        $token = DeveloperAccessToken::generate($actor->id);

        $token->title = $title;
        $token->last_activity_at = null;

        $token->save();

        $this->events->dispatch(new DeveloperTokenCreated($token));

        return $token;
    }
}
