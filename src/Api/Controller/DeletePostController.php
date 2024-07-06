<?php

namespace Forumkit\Api\Controller;

use Forumkit\Http\RequestUtil;
use Forumkit\Post\Command\DeletePost;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeletePostController extends AbstractDeleteController
{
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
    protected function delete(ServerRequestInterface $request)
    {
        $this->bus->dispatch(
            new DeletePost(Arr::get($request->getQueryParams(), 'id'), RequestUtil::getActor($request))
        );
    }
}
