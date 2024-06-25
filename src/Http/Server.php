<?php

namespace Forumkit\Http;

use Forumkit\Foundation\ErrorHandling\LogReporter;
use Forumkit\Foundation\SiteInterface;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator;
use Psr\Log\LoggerInterface;
use Throwable;

class Server
{
    private $site;

    public function __construct(SiteInterface $site)
    {
        $this->site = $site;
    }

    public function listen()
    {
        $runner = new RequestHandlerRunner(
            $this->safelyBootAndGetHandler(),
            new SapiEmitter,
            [ServerRequestFactory::class, 'fromGlobals'],
            function (Throwable $e) {
                $generator = new ErrorResponseGenerator;

                return $generator($e, new ServerRequest, new Response);
            }
        );
        $runner->run();
    }

    /**
     * Try to boot Forumkit, and retrieve the app's HTTP request handler.
     *
     * We catch all exceptions happening during this process and format them to
     * prevent exposure of sensitive information.
     *
     * @return \Psr\Http\Server\RequestHandlerInterface|void
     */
    private function safelyBootAndGetHandler()
    {
        try {
            return $this->site->bootApp()->getRequestHandler();
        } catch (Throwable $e) {
            // Apply response code first so whatever happens, it's set before anything is printed
            http_response_code(500);

            try {
                $this->cleanBootExceptionLog($e);
            } catch (Throwable $e) {
                // Ignore errors in logger. The important goal is to log the original error
            }

            $this->fallbackBootExceptionLog($e);
        }
    }

    /**
     * 尝试以干净的方式记录启动异常并停止脚本执行。
     * 这意味着寻找调试模式和/或我们正常的错误记录器。
     * 这总是有失败的风险，
     * 例如，如果容器绑定不存在或者如果存在文件系统错误。
     * @param Throwable $error
     */
    private function cleanBootExceptionLog(Throwable $error)
    {
        $container = resolve(Container::class);

        if ($container->has('forumkit.config') && resolve('forumkit.config')->inDebugMode()) {
            // 如果应用程序启动得足够远，配置可用，我们将检查调试模式 由于配置很早就加载了，因此它很可能可以从容器中获取
            $message = $error->getMessage();
            $file = $error->getFile();
            $line = $error->getLine();
            $type = get_class($error);

            echo <<<ERROR
            Forumkit 遇到引导错误 ($type)<br />
            <b>$message</b><br />
            扔进 <b>$file</b> 在线 <b>$line</b>

<pre>$error</pre>
ERROR;
            exit(1);
        } elseif ($container->has(LoggerInterface::class)) {
            // 如果应用程序启动得足够远，记录器可用，我们将在那里记录错误
            // 考虑到大多数引导错误都与数据库或扩展有关，记录器应该已经加载
            // 我们检查 LoggerInterface 绑定，因为它是 LogReporter 的构造函数依赖项，
            // 然后通过容器实例化 LogReporter 进行自动依赖注入
            resolve(LogReporter::class)->report($error);

            echo 'Forumkit 遇到引导错误。详细信息已记录到 Forumkit 日志文件中。';
            exit(1);
        }
    }

    /**
     * 如果干净的日志记录不起作用，那么我们还有最后一次机会。
     * 在这里，我们需要格外小心，不要在页面上包含任何可能敏感的内容。
     * @param Throwable $error
     * @throws Throwable
     */
    private function fallbackBootExceptionLog(Throwable $error)
    {
        echo 'Forumkit 遇到引导错误。详细信息已记录到系统 PHP 日志文件中。<br />';

        // 抛出异常可确保它在 PHP display_errors=On中可见但如果该功能关闭，则不可见PHP还会根据系统设置自动选择一个有效的地方进行记录
        throw $error;
    }
}
