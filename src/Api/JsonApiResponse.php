<?php

namespace Forumkit\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Tobscure\JsonApi\Document;

class JsonApiResponse extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Document $document, $status = 200, array $headers = [], $encodingOptions = 15)
    {
        $headers['content-type'] = 'application/vnd.api+json';

        // 对 jsonSerialize 的调用可防止 json_encode（） 失败并出现语法错误的罕见问题，即使 Document 实现了 JsonSerializable 接口也是如此。
        parent::__construct($document->jsonSerialize(), $status, $headers, $encodingOptions);
    }
}
