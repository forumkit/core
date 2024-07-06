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
 * 不要与 CreateTokenController 混淆，执行组件使用此控制器来手动创建开发人员类型访问令牌。
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
