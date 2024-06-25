<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\SiteSerializer;
use Forumkit\Group\Group;
use Forumkit\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowSiteController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = SiteSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['groups', 'actor', 'actor.groups'];

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        return [
            'groups' => Group::whereVisibleTo($actor)->get(),
            'actor' => $actor->isGuest() ? null : $actor
        ];
    }
}
