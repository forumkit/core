<?php

namespace Forumkit\Http\Middleware;

use Carbon\Carbon;
use Forumkit\Http\AccessToken;
use Forumkit\User\EmailToken;
use Forumkit\User\PasswordToken;
use Forumkit\User\RegistrationToken;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use SessionHandlerInterface;

class CollectGarbage implements Middleware
{
    /**
     * @var SessionHandlerInterface
     */
    protected $sessionHandler;

    /**
     * @var array
     */
    protected $sessionConfig;

    public function __construct(SessionHandlerInterface $handler, ConfigRepository $config)
    {
        $this->sessionHandler = $handler;
        $this->sessionConfig = $config->get('session');
    }

    public function process(Request $request, Handler $handler): Response
    {
        $this->collectGarbageSometimes();

        return $handler->handle($request);
    }

    private function collectGarbageSometimes()
    {
        // 为了节省性能，我们只不时执行此查询（有 2% 的几率）。
        if (! $this->hit()) {
            return;
        }

        $time = Carbon::now()->timestamp;

        AccessToken::whereExpired()->delete();

        $earliestToKeep = date('Y-m-d H:i:s', $time - 24 * 60 * 60);

        EmailToken::where('created_at', '<=', $earliestToKeep)->delete();
        PasswordToken::where('created_at', '<=', $earliestToKeep)->delete();
        RegistrationToken::where('created_at', '<=', $earliestToKeep)->delete();

        $this->sessionHandler->gc($this->getSessionLifetimeInSeconds());
    }

    private function hit()
    {
        return mt_rand(1, 100) <= 2;
    }

    private function getSessionLifetimeInSeconds()
    {
        return $this->sessionConfig['lifetime'] * 60;
    }
}
