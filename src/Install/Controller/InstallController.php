<?php

namespace Forumkit\Install\Controller;

use Forumkit\Http\RememberAccessToken;
use Forumkit\Http\Rememberer;
use Forumkit\Http\SessionAuthenticator;
use Forumkit\Install\AdminUser;
use Forumkit\Install\BaseUrl;
use Forumkit\Install\DatabaseConfig;
use Forumkit\Install\Installation;
use Forumkit\Install\StepFailed;
use Forumkit\Install\ValidationFailed;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class InstallController implements RequestHandlerInterface
{
    /**
     * @var Installation
     */
    protected $installation;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * InstallController 构造函数
     * @param Installation $installation 安装服务实例，用于处理安装流程
     * @param SessionAuthenticator $authenticator 会话认证器实例，用于处理会话认证
     * @param Rememberer $rememberer 记住器实例，用于处理用户的登录记忆功能
     */
    public function __construct(Installation $installation, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->installation = $installation;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();
        $baseUrl = BaseUrl::fromUri($request->getUri());

        // 生成一个访问令牌，用于在安装结束时自动登录管理员
        $accessToken = Str::random(40);

        try {
            $pipeline = $this->installation
                ->baseUrl($baseUrl)
                ->databaseConfig($this->makeDatabaseConfig($input))
                ->adminUser($this->makeAdminUser($input))
                ->accessToken($accessToken)
                ->settings([
                    'site_name' => Arr::get($input, 'forumTitle'),
                    'mail_from' => $baseUrl->toEmail('noreply'),
                  //  'welcome_title' => 'Welcome to '.Arr::get($input, 'forumTitle'),
                ])
                ->build();
        } catch (ValidationFailed $e) {
            return new Response\HtmlResponse($e->getMessage(), 500);
        }

        try {
            $pipeline->run();
        } catch (StepFailed $e) {
            return new Response\HtmlResponse($e->getPrevious()->getMessage(), 500);
        }

        $session = $request->getAttribute('session');
        // 由于Eloquent模型此时可能还不能使用，我们创建一个临时的内存对象
        // 该对象不会与数据库交互，但可以传递给认证器和记住器
        $token = new RememberAccessToken();
        $token->token = $accessToken;
        $this->authenticator->logIn($session, $token);

        return $this->rememberer->remember(new Response\EmptyResponse, $token);
    }

    private function makeDatabaseConfig(array $input): DatabaseConfig
    {
        $host = Arr::get($input, 'mysqlHost');
        $port = 3306;

        if (Str::contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        return new DatabaseConfig(
            'mysql',
            $host,
            intval($port),
            Arr::get($input, 'mysqlDatabase'),
            Arr::get($input, 'mysqlUsername'),
            Arr::get($input, 'mysqlPassword'),
            Arr::get($input, 'tablePrefix')
        );
    }

    /**
     * @param array $input
     * @return AdminUser
     * @throws ValidationFailed
     */
    private function makeAdminUser(array $input): AdminUser
    {
        return new AdminUser(
            Arr::get($input, 'adminUsername'),
            $this->getConfirmedAdminPassword($input),
            Arr::get($input, 'adminEmail')
        );
    }

    private function getConfirmedAdminPassword(array $input): string
    {
        $password = Arr::get($input, 'adminPassword');
        $confirmation = Arr::get($input, 'adminPasswordConfirmation');

        if ($password !== $confirmation) {
            throw new ValidationFailed('The admin password did not match its confirmation.');
        }

        return $password;
    }
}
