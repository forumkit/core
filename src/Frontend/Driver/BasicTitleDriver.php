<?php

namespace Forumkit\Frontend\Driver;

use Forumkit\Frontend\Document;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicTitleDriver implements TitleDriverInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function makeTitle(Document $document, ServerRequestInterface $request, array $siteApiDocument): string
    {
        $onHomePage = rtrim($request->getUri()->getPath(), '/') === '';

        $params = [
            'pageTitle' => $document->title ?? '',
            'siteName' => Arr::get($siteApiDocument, 'data.attributes.title'),
            'pageNumber' => $document->page ?? 1,
        ];

        return $onHomePage || ! $document->title
            ? $this->translator->trans('core.lib.meta_titles.without_page_title', $params)
            : $this->translator->trans('core.lib.meta_titles.with_page_title', $params);
    }
}
