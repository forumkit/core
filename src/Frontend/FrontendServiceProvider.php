<?php

namespace Forumkit\Frontend;

use Forumkit\Foundation\AbstractServiceProvider;
use Forumkit\Foundation\Paths;
use Forumkit\Frontend\Compiler\Source\SourceCollector;
use Forumkit\Frontend\Driver\BasicTitleDriver;
use Forumkit\Frontend\Driver\TitleDriverInterface;
use Forumkit\Http\SlugManager;
use Forumkit\Http\UrlGenerator;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;

class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('forumkit.assets.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $paths = $container[Paths::class];

                $assets = new Assets(
                    $name,
                    $container->make('filesystem')->disk('forumkit-assets'),
                    $paths->storage,
                    null,
                    $container->make('forumkit.frontend.custom_less_functions')
                );

                $assets->setLessImportDirs([
                    $paths->vendor.'/components/font-awesome/less' => ''
                ]);

                $assets->css([$this, 'addBaseCss']);
                $assets->localeCss([$this, 'addBaseCss']);

                return $assets;
            };
        });

        $this->container->singleton('forumkit.frontend.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $frontend = $container->make(Frontend::class);

                $frontend->content(function (Document $document) use ($name) {
                    $document->layoutView = 'forumkit::frontend.'.$name;
                });

                $frontend->content($container->make(Content\Assets::class)->forFrontend($name));
                $frontend->content($container->make(Content\CorePayload::class));
                $frontend->content($container->make(Content\Meta::class));

                $frontend->content(function (Document $document) use ($container) {
                    $default_preloads = $container->make('forumkit.frontend.default_preloads');

                    // 添加基础 CSS 和 JS 资源的预加载。扩展插件应该通过扩展器添加它们自己的预加载资源。
                    $js_preloads = [];
                    $css_preloads = [];

                    foreach ($document->css as $url) {
                        $css_preloads[] = [
                            'href' => $url,
                            'as' => 'style'
                        ];
                    }
                    foreach ($document->js as $url) {
                        $css_preloads[] = [
                            'href' => $url,
                            'as' => 'script'
                        ];
                    }

                    $document->preloads = array_merge(
                        $css_preloads,
                        $js_preloads,
                        $default_preloads,
                        $document->preloads,
                    );
                });

                return $frontend;
            };
        });

        $this->container->singleton(
            'forumkit.frontend.default_preloads',
            function (Container $container) {
                $filesystem = $container->make('filesystem')->disk('forumkit-assets');

                return [
                    [
                        'href' => $filesystem->url('fonts/fa-solid-900.woff2'),
                        'as' => 'font',
                        'type' => 'font/woff2',
                        'crossorigin' => ''
                    ], [
                        'href' => $filesystem->url('fonts/fa-regular-400.woff2'),
                        'as' => 'font',
                        'type' => 'font/woff2',
                        'crossorigin' => ''
                    ]
                ];
            }
        );

        $this->container->singleton(
            'forumkit.frontend.custom_less_functions',
            function (Container $container) {
                $extensionsEnabled = json_decode($container->make(SettingsRepositoryInterface::class)->get('extensions_enabled'));

                // 请注意，这些函数不经过主题扩展器的 `addCustomLessFunction` 方法所做的相同转换。
                // 你需要使用正确的 Less 树返回类型，并通过 `$arg->value` 获取参数值。
                return [
                    'is-extension-enabled' => function (\Less_Tree_Quoted $extensionId) use ($extensionsEnabled) {
                        return new \Less_Tree_Quoted('', in_array($extensionId->value, $extensionsEnabled) ? 'true' : 'false');
                    }
                ];
            }
        );

        $this->container->singleton(TitleDriverInterface::class, function (Container $container) {
            return $container->make(BasicTitleDriver::class);
        });

        $this->container->alias(TitleDriverInterface::class, 'forumkit.frontend.title_driver');

        $this->container->singleton('forumkit.less.config', function (Container $container) {
            return [
                'config-primary-color'   => [
                    'key' => 'theme_primary_color',
                ],
                'config-secondary-color' => [
                    'key' => 'theme_secondary_color',
                ],
                'config-dark-mode'       => [
                    'key' => 'theme_dark_mode',
                    'callback' => function ($value) {
                        return $value ? 'true' : 'false';
                    },
                ],
                'config-colored-header'  => [
                    'key' => 'theme_colored_header',
                    'callback' => function ($value) {
                        return $value ? 'true' : 'false';
                    },
                ],
            ];
        });

        $this->container->singleton(
            'forumkit.less.custom_variables',
            function (Container $container) {
                return [];
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, ViewFactory $views)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'forumkit');

        $views->share([
            'translator' => $container->make('translator'),
            'url' => $container->make(UrlGenerator::class),
            'slugManager' => $container->make(SlugManager::class)
        ]);
    }

    public function addBaseCss(SourceCollector $sources)
    {
        $sources->addFile(__DIR__.'/../../less/common/variables.less');
        $sources->addFile(__DIR__.'/../../less/common/mixins.less');

        $this->addLessVariables($sources);
    }

    private function addLessVariables(SourceCollector $sources)
    {
        $sources->addString(function () {
            $vars = $this->container->make('forumkit.less.config');
            $extDefinedVars = $this->container->make('forumkit.less.custom_variables');

            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $customLess = array_reduce(array_keys($vars), function ($string, $name) use ($vars, $settings) {
                $var = $vars[$name];
                $value = $settings->get($var['key'], $var['default'] ?? null);

                if (isset($var['callback'])) {
                    $value = $var['callback']($value);
                }

                return $string."@$name: {$value};";
            }, '');

            foreach ($extDefinedVars as $name => $value) {
                $customLess .= "@$name: {$value()};";
            }

            return $customLess;
        });
    }
}
