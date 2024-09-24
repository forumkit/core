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
 * 注意不要与用于用户登录并生成会话式访问令牌的 CreateTokenController 混淆，
 * 这个控制器（ CreateAccessTokenController ）是由用户（或称为 actor ）手动用来创建开发者类型的访问令牌的。
 * 它继承自 AbstractCreateController ，可能包含了一些通用的创建逻辑或依赖注入。
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
