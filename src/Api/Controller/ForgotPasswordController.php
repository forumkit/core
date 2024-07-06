<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\ForgotPasswordValidator;
use Forumkit\User\Job\RequestPasswordResetJob;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForgotPasswordController implements RequestHandlerInterface
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var ForgotPasswordValidator
     */
    protected $validator;

    public function __construct(Queue $queue, ForgotPasswordValidator $validator)
    {
        $this->queue = $queue;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        $this->validator->assertValid($params);

        $email = Arr::get($params, 'email');

        // 通过不引发错误来防止泄漏用户的存在。
        // 通过使用排队的作业，按持续时间防止泄漏用户存在。
        $this->queue->push(new RequestPasswordResetJob($email));

        return new EmptyResponse;
    }
}
