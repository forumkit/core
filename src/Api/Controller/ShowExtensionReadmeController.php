<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\Serializer\ExtensionReadmeSerializer;
use Forumkit\Extension\ExtensionManager;
use Forumkit\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowExtensionReadmeController extends AbstractShowController
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * {@inheritdoc}
     */
    public $serializer = ExtensionReadmeSerializer::class;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $extensionName = Arr::get($request->getQueryParams(), 'name');

        RequestUtil::getActor($request)->assertAdmin();

        return $this->extensions->getExtension($extensionName);
    }
}
