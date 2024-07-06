<?php

namespace Forumkit\Api\Controller;

use Forumkit\Discussion\Command\DeleteDiscussion;
use Forumkit\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteDiscussionController extends AbstractDeleteController
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
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);
        $input = $request->getParsedBody();

        $this->bus->dispatch(
            new DeleteDiscussion($id, $actor, $input)
        );
    }
}
