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
     * InstallController 构造函数。
     * @param Installation $installation 安装服务实例
     * @param SessionAuthenticator $authenticator 会话验证器实例
     * @param Rememberer $rememberer 记忆器实例
     */
    public function __construct(Installation $installation, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->installation = $installation; // 安装服务实例
        $this->authenticator = $authenticator; // 会话验证器实例
        $this->rememberer = $rememberer; // 记忆器实例
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();
        $baseUrl = BaseUrl::fromUri($request->getUri());

        // 创建一个用于在安装结束时自动登录管理员的访问令牌
        $accessToken = Str::random(40);

        try {
            $pipeline = $this->installation
                ->baseUrl($baseUrl) // 设置基础URL
                ->databaseConfig($this->makeDatabaseConfig($input)) // 设置数据库配置
                ->adminUser($this->makeAdminUser($input)) // 设置管理员用户
                ->accessToken($accessToken) // 设置访问令牌
                ->settings([ // 设置其他配置
                    'site_title' => Arr::get($input, 'siteTitle'), // 网站标题
                    'mail_from' => $baseUrl->toEmail('noreply'), // 发件人邮箱
                    'welcome_title' => 'Welcome to '.Arr::get($input, 'siteTitle'), // 欢迎标题
                ])
                ->build(); // 构建安装流程
        } catch (ValidationFailed $e) {
            // 如果验证失败，返回带有错误信息的HTML响应
            return new Response\HtmlResponse($e->getMessage(), 500);
        }

        try {
            $pipeline->run(); // 运行安装流程
        } catch (StepFailed $e) {
            // 如果安装步骤失败，返回带有错误信息的HTML响应
            return new Response\HtmlResponse($e->getPrevious()->getMessage(), 500);
        }

        $session = $request->getAttribute('session'); // 获取会话
        // 因为Eloquent模型还不能使用，我们创建一个临时的内存对象
        // 它不会与数据库交互，但可以传递给验证器和记忆器
        $token = new RememberAccessToken();
        $token->token = $accessToken;
        $this->authenticator->logIn($session, $token); // 将会话与访问令牌关联，进行登录

        return $this->rememberer->remember(new Response\EmptyResponse, $token); // 使用记忆器记住登录状态
    }

    private function makeDatabaseConfig(array $input): DatabaseConfig
    {
        $host = Arr::get($input, 'mysqlHost'); // 数据库主机名
        $port = 3306; // 默认端口

        if (Str::contains($host, ':')) {
            // 如果主机名中包含端口号，则分割它们
            list($host, $port) = explode(':', $host, 2);
        }

        return new DatabaseConfig(
            'mysql', // 数据库驱动
            $host, // 主机名
            intval($port), // 端口号
            Arr::get($input, 'mysqlDatabase'), // 数据库名
            Arr::get($input, 'mysqlUsername'), // 用户名
            Arr::get($input, 'mysqlPassword'), // 密码
            Arr::get($input, 'tablePrefix') // 表前缀
        );
    }

    /**
     * 从输入数组创建管理员用户对象
     * 
     * @param array $input 输入数组
     * @return AdminUser 管理员用户对象
     * @throws ValidationFailed 如果密码和确认密码不匹配则抛出异常
     */
    private function makeAdminUser(array $input): AdminUser
    {
        return new AdminUser(
            Arr::get($input, 'adminUsername'), // 用户名
            $this->getConfirmedAdminPassword($input), // 密码
            Arr::get($input, 'adminEmail') // 邮箱
        );
    }

    private function getConfirmedAdminPassword(array $input): string
    {
        $password = Arr::get($input, 'adminPassword'); // 从输入数组中获取管理员密码
        $confirmation = Arr::get($input, 'adminPasswordConfirmation'); // 从输入数组中获取管理员密码的确认

        if ($password !== $confirmation) {
            // 如果密码和确认密码不匹配
            throw new ValidationFailed('管理员密码与其确认密码不匹配。');
        }

        return $password;
    }
}
