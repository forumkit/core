<?php

namespace Forumkit\Frontend\Content;

use Forumkit\Foundation\Config;
use Forumkit\Frontend\Compiler\CompilerInterface;
use Forumkit\Frontend\Document;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Assets
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Forumkit\Frontend\Assets
     */
    protected $assets;

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * 设置要为其生成资源的前端。
     *
     * @param string $name frontend name
     * @return $this
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function forFrontend(string $name): Assets
    {
        $this->assets = $this->container->make('forumkit.assets.'.$name);

        return $this;
    }

    public function __invoke(Document $document, Request $request)
    {
        $locale = $request->getAttribute('locale');

        $compilers = $this->assembleCompilers($locale);

        if ($this->config->inDebugMode()) {
            $this->forceCommit(Arr::flatten($compilers));
        }

        $this->addAssetsToDocument($document, $compilers);
    }

    /**
     * 组装用于生成前端资源的 JS 和 CSS 编译器。
     *
     * @param string|null $locale
     * @return array[]
     */
    protected function assembleCompilers(?string $locale): array
    {
        return [
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)],
            'css' => [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)]
        ];
    }

    /**
     * 将前端 JS 和 CSS 的 URL 添加到 {@link Document} 类中。
     *
     * @param Document $document
     * @param array $compilers
     * @return void
     */
    protected function addAssetsToDocument(Document $document, array $compilers): void
    {
        $document->js = array_merge($document->js, $this->getUrls($compilers['js']));
        $document->css = array_merge($document->css, $this->getUrls($compilers['css']));
    }

    /**
     * 当处于调试模式时，强制编译资源。
     *
     * @param array $compilers
     */
    protected function forceCommit(array $compilers): void
    {
        /** @var CompilerInterface $compiler */
        foreach ($compilers as $compiler) {
            $compiler->commit(true);
        }
    }

    /**
     * 将提供的 {@link CompilerInterface} 映射到其 URL。
     *
     * @param CompilerInterface[] $compilers
     * @return string[]
     */
    protected function getUrls(array $compilers): array
    {
        return array_filter(array_map(function (CompilerInterface $compiler) {
            return $compiler->getUrl();
        }, $compilers));
    }
}
