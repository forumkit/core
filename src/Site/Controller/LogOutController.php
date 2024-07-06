<?php

namespace Forumkit\Site\Controller;

use Forumkit\Foundation\Config;
use Forumkit\Http\Exception\TokenMismatchException;
use Forumkit\Http\Rememberer;
use Forumkit\Http\RequestUtil;
use Forumkit\Http\SessionAuthenticator;
use Forumkit\Http\UrlGenerator;
use Forumkit\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogOutController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Dispatcher $events
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     * @param Factory $view
     * @param UrlGenerator $url
     * @param Config $config
     */
    public function __construct(
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        Factory $view,
        UrlGenerator $url,
        Config $config
    ) {
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->view = $view;
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);
        $base = $this->url->to('site')->base();

        $returnUrl = Arr::get($request->getQueryParams(), 'return');
        $return = $this->sanitizeReturnUrl((string) $returnUrl, $base);

        // 如果没有用户登录，请返回索引或返回 URL（如果已设置）。
        if ($actor->isGuest()) {
            return new RedirectResponse($return);
        }

        // 如果没有提供有效的 CSRF 令牌，请显示一个视图，该视图允许用户按下按钮来完成注销过程。
        $csrfToken = $session->token();

        if (Arr::get($request->getQueryParams(), 'token') !== $csrfToken) {
            $view = $this->view->make('forumkit.site::log-out')
                ->with('url', $this->url->to('site')->route('logout') . '?token=' . $csrfToken . ($returnUrl ? '&return=' . urlencode($return) : ''));

            return new HtmlResponse($view->render());
        }

        $accessToken = $session->get('access_token');
        $response = new RedirectResponse($return);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->where('token', $accessToken)->delete();

        $this->events->dispatch(new LoggedOut($actor, false));

        return $this->rememberer->forget($response);
    }

    protected function sanitizeReturnUrl(string $url, string $base): Uri
    {
        if (empty($url)) {
            return new Uri($base);
        }

        try {
            $parsedUrl = new Uri($url);
        } catch (\InvalidArgumentException $e) {
            return new Uri($base);
        }

        if (in_array($parsedUrl->getHost(), $this->getAllowedRedirectDomains())) {
            return $parsedUrl;
        }

        return new Uri($base);
    }

    protected function getAllowedRedirectDomains(): array
    {
        $siteUri = $this->config->url();

        return array_merge(
            [$siteUri->getHost()],
            $this->config->offsetGet('redirectDomains') ?? []
        );
    }
}
