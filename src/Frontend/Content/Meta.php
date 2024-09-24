<?php

namespace Forumkit\Frontend\Content;

use Forumkit\Frontend\Document;
use Forumkit\Locale\LocaleManager;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Meta
{
    /**
     * @var LocaleManager
     */
    private $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    public function __invoke(Document $document, Request $request)
    {
        $document->language = $this->locales->getLocale();
        $document->direction = 'ltr';
        $document->meta = array_merge($document->meta, $this->buildMeta($document));
        $document->head = array_merge($document->head, $this->buildHead($document));
    }

    /**
     * 构建页面的meta信息
     */
    private function buildMeta(Document $document)
    {
        // 从文档对象中获取论坛API文档数据
        $forumApiDocument = $document->getForumApiDocument();

        // 构建meta数组
        $meta = [
            // 设置视口属性，用于响应式设计
            'viewport' => 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1',
            // 从论坛API文档数据中获取页面描述，并设置到meta数组中
            'description' => Arr::get($forumApiDocument, 'data.attributes.description'),
            // 从论坛API文档数据中获取主题主色，并设置到meta数组中
            // 'theme-color' => Arr::get($forumApiDocument, 'data.attributes.themePrimaryColor')
        ];

        // 返回构建好的meta数组
        return $meta;
    }

    private function buildHead(Document $document)
    {
        $head = [];

        if ($faviconUrl = Arr::get($document->getForumApiDocument(), 'data.attributes.faviconUrl')) {
            $head['favicon'] = '<link rel="shortcut icon" href="'.e($faviconUrl).'">';
        }

        return $head;
    }
}
