<?php

namespace Forumkit\Foundation\ErrorHandling;

use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * 一个格式化器，用于将捕获的异常转换为漂亮的HTML错误页面。
 *
 * 对于某些已知的错误类型，我们会显示具有针对该类错误专用信息的页面，
 * 例如，对于HTTP 404“未找到”错误，我们会显示一个带有搜索表单的页面。
 * 我们在 `views/error` 目录中查找模板。
 *
 * 如果没有特定的模板存在，则会显示一个通用的“出现问题”页面，
 * 如果翻译文件中找到了更具体的错误信息，则可以选择性地丰富该页面。
 */
class ViewFormatter implements HttpFormatter
{
    /**
     * @var ViewFactory
     */
    protected $view;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(ViewFactory $view, TranslatorInterface $translator, SettingsRepositoryInterface $settings)
    {
        $this->view = $view;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    public function format(HandledError $error, Request $request): Response
    {
        $view = $this->view->make($this->determineView($error))
            ->with('error', $error->getException())
            ->with('message', $this->getMessage($error));

        return new HtmlResponse($view->render(), $error->getStatusCode());
    }

    const ERRORS_WITH_VIEWS = ['csrf_token_mismatch', 'not_found'];

    private function determineView(HandledError $error): string
    {
        $type = $error->getType();

        if (in_array($type, self::ERRORS_WITH_VIEWS)) {
            return "forumkit.site::error.$type";
        } else {
            return 'forumkit.site::error.default';
        }
    }

    private function getMessage(HandledError $error)
    {
        return $this->getTranslationIfExists($error->getType())
            ?? $this->getTranslationIfExists('unknown')
            ?? '尝试加载此页面时出错。';
    }

    private function getTranslationIfExists(string $errorType)
    {
        $key = "core.views.error.$errorType";
        $translation = $this->translator->trans($key, ['site' => $this->settings->get('site_title')]);

        return $translation === $key ? null : $translation;
    }
}
