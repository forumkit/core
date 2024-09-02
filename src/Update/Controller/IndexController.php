<?php

namespace Forumkit\Update\Controller;

use Forumkit\Http\Controller\AbstractHtmlController;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    public function render(Request $request)
    {
        $view = $this->view->make('forumkit.update::app')->with('title', 'Update Forumkit');

        $view->with('content', $this->view->make('forumkit.update::update'));

        return $view;
    }
}
