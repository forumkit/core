<?php

namespace Forumkit\Admin\Content;

use Forumkit\Extension\ExtensionManager;
use Forumkit\Foundation\Application;
use Forumkit\Frontend\Document;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class Index
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Factory $view, ExtensionManager $extensions, SettingsRepositoryInterface $settings)
    {
        $this->view = $view;
        $this->extensions = $extensions;
        $this->settings = $settings;
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $extensions = $this->extensions->getExtensions();
        $extensionsEnabled = json_decode($this->settings->get('extensions_enabled', '{}'), true);
        $csrfToken = $request->getAttribute('session')->token();

        $mysqlVersion = $document->payload['mysqlVersion'];
        $phpVersion = $document->payload['phpVersion'];
        $forumkitVersion = Application::VERSION;

        $document->content = $this->view->make(
            'forumkit.admin::frontend.content.admin',
            compact('extensions', 'extensionsEnabled', 'csrfToken', 'forumkitVersion', 'phpVersion', 'mysqlVersion')
        );

        return $document;
    }
}
