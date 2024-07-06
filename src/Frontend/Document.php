<?php

namespace Forumkit\Frontend;

use Forumkit\Frontend\Driver\TitleDriverInterface;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 一个视图，用于为 Forumkit 的前端应用渲染 HTML 骨架。
 */
class Document implements Renderable
{
    /**
     * 文档的标题
     *
     * @var null|string
     */
    public $title;

    /**
     * 文档的语言，显示为 标签中 `lang` 属性的值。
     *
     * @var null|string
     */
    public $language;

    /**
     * 文档的文本方向，显示为 标签中 `dir` 属性的值。
     *
     * @var null|string
     */
    public $direction;

    /**
     * 要显示的前端应用视图的名称。
     *
     * @var string
     */
    public $appView = 'forumkit::frontend.app';

    /**
     * 要显示的前端布局视图的名称。
     *
     * @var string
     */
    public $layoutView;

    /**
     * 要显示的前端内容视图的名称。
     *
     * @var string
     */
    public $contentView = 'forumkit::frontend.content';

    /**
     * 页面的 SEO 内容，在布局中的
     *
     * @var string|Renderable
     */
    public $content;

    /**
     * 预加载到 Forumkit JS 中的其他变量。
     *
     * @var array
     */
    public $payload = [];

    /**
     * 要附加到页面 <head> 的元标签数组。
     *
     * @var array
     */
    public $meta = [];

    /**
     * 此页面的 canonical URL。
     *
     * 如果内容可以在多个地址下找到，这将向搜索引擎发出信号，指明应该使用哪个 URL
     * 用于此内容。这是处理重复内容的重要工具。
     *
     * @var null|string
     */
    public $canonicalUrl;

    /**
     * 我们当前处于哪一页内容？
     *
     * 这用于为 SEO 构建 prev/next 元链接。
     *
     * @var null|int
     */
    public $page;

    /**
     * 是否有下一页？
     *
     * 这与 $page 一起使用，为 SEO 构建 next 元链接。
     *
     * @var null|bool
     */
    public $hasNextPage;

    /**
     * 要附加到页面 <head> 的字符串数组。
     *
     * @var array
     */
    public $head = [];

    /**
     * 要在页面  </body> 之前添加的字符串数组。
     *
     * @var array
     */
    public $foot = [];

    /**
     * 要加载的 JavaScript URL 数组。
     *
     * @var array
     */
    public $js = [];

    /**
     * 要加载的 CSS URL 数组。
     *
     * @var array
     */
    public $css = [];

    /**
     * 预加载的资源数组。
     *
     * 每个数组项应该是一个包含与 `<link rel="preload">` 标签相关的键的数组。
     *
     * 例如，以下将为 FontAwesome 字体文件添加一个预加载标签：
     * ```
     * $this->preloads[] = [
     *   'href' => '/assets/fonts/fa-solid-900.woff2',
     *   'as' => 'font',
     *   'type' => 'font/woff2',
     *   'crossorigin' => ''
     * ];
     * ```
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types/preload
     *
     * @var array
     */
    public $preloads = [];

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var array
     */
    protected $siteApiDocument;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Factory $view, array $siteApiDocument, Request $request)
    {
        $this->view = $view;
        $this->siteApiDocument = $siteApiDocument;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $this->view->share('site', Arr::get($this->siteApiDocument, 'data.attributes'));

        return $this->makeView()->render();
    }

    /**
     * @return View
     */
    protected function makeView(): View
    {
        return $this->view->make($this->appView)->with([
            'title' => $this->makeTitle(),
            'payload' => $this->payload,
            'layout' => $this->makeLayout(),
            'language' => $this->language,
            'direction' => $this->direction,
            'js' => $this->makeJs(),
            'head' => $this->makeHead(),
            'foot' => $this->makeFoot(),
        ]);
    }

    /**
     * @return string
     */
    protected function makeTitle(): string
    {
        // @todo v2.0 改为依赖注入而不是这样调用
        return resolve(TitleDriverInterface::class)->makeTitle($this, $this->request, $this->siteApiDocument);
    }

    protected function makeLayout(): ?View
    {
        if ($this->layoutView) {
            return $this->view->make($this->layoutView)->with('content', $this->makeContent());
        }

        return null;
    }

    /**
     * @return View
     */
    protected function makeContent(): View
    {
        return $this->view->make($this->contentView)->with('content', $this->content);
    }

    protected function makePreloads(): array
    {
        return array_map(function ($preload) {
            $attributes = '';

            foreach ($preload as $key => $value) {
                $attributes .= " $key=\"".e($value).'"';
            }

            return "<link rel=\"preload\"$attributes>";
        }, $this->preloads);
    }

    /**
     * @return string
     */
    protected function makeHead(): string
    {
        $head = array_map(function ($url) {
            return '<link rel="stylesheet" href="'.e($url).'">';
        }, $this->css);

        if ($this->page) {
            if ($this->page > 1) {
                $head[] = '<link rel="prev" href="'.e(self::setPageParam($this->canonicalUrl, $this->page - 1)).'">';
            }
            if ($this->hasNextPage) {
                $head[] = '<link rel="next" href="'.e(self::setPageParam($this->canonicalUrl, $this->page + 1)).'">';
            }
        }

        if ($this->canonicalUrl) {
            $head[] = '<link rel="canonical" href="'.e(self::setPageParam($this->canonicalUrl, $this->page)).'">';
        }

        $head = array_merge($head, $this->makePreloads());

        $head = array_merge($head, array_map(function ($content, $name) {
            return '<meta name="'.e($name).'" content="'.e($content).'">';
        }, $this->meta, array_keys($this->meta)));

        return implode("\n", array_merge($head, $this->head));
    }

    /**
     * @return string
     */
    protected function makeJs(): string
    {
        return implode("\n", array_map(function ($url) {
            return '<script src="'.e($url).'"></script>';
        }, $this->js));
    }

    /**
     * @return string
     */
    protected function makeFoot(): string
    {
        return implode("\n", $this->foot);
    }

    /**
     * @return array
     */
    public function getSiteApiDocument(): array
    {
        return $this->siteApiDocument;
    }

    /**
     * @param array $siteApiDocument
     */
    public function setSiteApiDocument(array $siteApiDocument)
    {
        $this->siteApiDocument = $siteApiDocument;
    }

    public static function setPageParam(string $url, ?int $page)
    {
        if (! $page || $page === 1) {
            return self::setQueryParam($url, 'page', null);
        }

        return self::setQueryParam($url, 'page', (string) $page);
    }

    /**
     * 在字符串URL上设置或覆盖查询参数为特定值。
     */
    protected static function setQueryParam(string $url, string $key, ?string $value)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $urlParts = parse_url($url);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $urlQueryArgs);

                if ($value === null) {
                    unset($urlQueryArgs[$key]);
                } else {
                    $urlQueryArgs[$key] = $value;
                }

                $urlParts['query'] = http_build_query($urlQueryArgs);
                $newUrl = $urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'?'.$urlParts['query'];
            } elseif ($value !== null) {
                $newUrl = $url.'?'.http_build_query([$key => $value]);
            } else {
                return $url;
            }

            return $newUrl;
        } else {
            return $url;
        }
    }
}
