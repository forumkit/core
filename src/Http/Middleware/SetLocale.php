<?php

namespace Forumkit\Http\Middleware;

use Forumkit\Http\RequestUtil;
use Forumkit\Locale\LocaleManager;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class SetLocale implements Middleware
{
    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $actor = RequestUtil::getActor($request);

        if ($actor->exists) {
            $locale = $actor->getPreference('locale');
        } else {
            $locale = Arr::get($request->getCookieParams(), 'locale');
        }

        if ($locale && $this->locales->hasLocale($locale)) {
            $this->locales->setLocale($locale);
        }

        $request = $request->withAttribute('locale', $this->locales->getLocale());

        return $handler->handle($request);
    }
}
