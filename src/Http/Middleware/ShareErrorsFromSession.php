<?php

namespace Forumkit\Http\Middleware;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ViewErrorBag;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * 受 Illuminate\View\Middleware\ShareErrorsFromSession 启发
 *
 * @author Taylor Otwell
 */
class ShareErrorsFromSession implements Middleware
{
    /**
     * @var ViewFactory
     */
    protected $view;

    /**
     * @param ViewFactory $view
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $session = $request->getAttribute('session');

        // 如果当前会话绑定了一个 "errors" 变量，我们将它的值共享给所有的视图实例
        // 这样视图就可以轻松访问错误，而无需绑定。如果没有错误，则设置一个空的袋子。
        $this->view->share(
            'errors',
            $session->get('errors', new ViewErrorBag)
        );

        // 将错误放入每个视图中，允许开发者假设一些错误总是可用的，这很方便
        // 因为他们不必持续检查错误是否存在。

        $session->remove('errors');

        return $handler->handle($request);
    }
}
