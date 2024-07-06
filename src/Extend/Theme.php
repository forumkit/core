<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Frontend\Assets;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class Theme implements ExtenderInterface
{
    private $lessImportOverrides = [];
    private $fileSourceOverrides = [];
    private $customFunctions = [];
    private $lessVariables = [];

    /**
     * 这可用于覆盖代码中导入的 LESS 文件。
     * 例如，core 的  `site.less` 文件会导入一个 `site/DiscussionListItem.less` 文件。
     * 此文件的内容可以用此方法覆盖。
     *
     * @param string $file : 要覆盖的文件的相对路径，例如： `site/Hero.less`
     * @param string $newFilePath : 新文件的绝对路径
     * @param string|null $extensionId : 如果覆盖扩展文件，请指定其 ID，例如： `forumkit-tags`
     * @return self
     */
    public function overrideLessImport(string $file, string $newFilePath, string $extensionId = null): self
    {
        $this->lessImportOverrides[] = compact('file', 'newFilePath', 'extensionId');

        return $this;
    }

    /**
     * 此方法允许覆盖 LESS 文件源。
     * 例如，`site.less`, `admin.less`, `mixins.less` 和 `variables.less` 都是文件源，
     * 因此可以使用此方法覆盖它们。
     *
     * @param string $file : 要覆盖的文件名，例如：`admin.less`
     * @param string $newFilePath : 新文件的绝对路径
     * @param string|null $extensionId : 如果要覆盖扩展文件，请指定其 ID，例如：`forumkit-tags`.
     * @return self
     */
    public function overrideFileSource(string $file, string $newFilePath, string $extensionId = null): self
    {
        $this->fileSourceOverrides[] = compact('file', 'newFilePath', 'extensionId');

        return $this;
    }

    /**
     * 此方法允许您添加自定义的 Less 函数。
     *
     * 所有自定义的 Less 函数只能返回数字、字符串或布尔值。
     *
     * **使用示例**
     * ```php
     * (new Extend\Theme)
     *     ->addCustomLessFunction('is-forumkit', function (mixed $text) {
     *         return strtolower($text) === 'forumkit'
     *     }),
     * ```
     *
     * @see https://leafo.net/lessphp/docs/#custom_functions
     *
     * @param string $functionName 函数标识符的名称
     * @param callable $callable 当调用 Less 函数时要运行的 PHP 函数
     * @return self
     */
    public function addCustomLessFunction(string $functionName, callable $callable): self
    {
        $this->customFunctions[$functionName] = function (...$args) use ($callable, $functionName) {
            $argVals = array_map(function ($arg) {
                return $arg->value;
            }, $args);

            $return = $callable(...$argVals);

            if (is_bool($return)) {
                return new \Less_Tree_Quoted('', $return ? 'true' : 'false');
            }

            if (is_string($return)) {
                return new \Less_Tree_Quoted('', $return);
            }

            if (is_numeric($return)) {
                return new \Less_Tree_Dimension($return);
            }

            throw new RuntimeException('自定义 Less 函数 `'.$functionName.'` 只能返回字符串、数字或布尔值。');
        };

        return $this;
    }

    /**
     * 定义一个新的 Less 变量，以便在所有 Less 文件中访问。
     *
     * 如果您想从数据库中公开一个设置到 Less，您应该使用
     * `Extend\Settings` 中的 `registerLessConfigVar` 扩展器。
     *
     * 请注意，从可调用函数返回的值将直接插入到 Less 源码中。
     * 如果它以某种方式不安全（例如，包含分号），这可能会导致您的样式表出现潜在的安全问题。
     *
     * 同样，如果您需要您的变量是一个字符串，您应该自己添加引号。
     *
     * ```php
     * (new Extend\Theme())
     *   ->addCustomLessVariable('my-extension__asset_path', function () {
     *     $url = resolve(UrlGenerator::class);
     *     $assetUrl = $url->to('site')->base().'/assets/extensions/my-extension/my-asset.jpg';
     *     return "\"$assetUrl\"";
     *   })
     * ```
     *
     * @param string $variableName 变量标识符的名称
     * @param callable $value 当调用变量时要运行的 PHP 函数，它返回变量的值
     *
     * @return self
     */
    public function addCustomLessVariable(string $variableName, callable $value): self
    {
        $this->lessVariables[$variableName] = $value;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.frontend.custom_less_functions', function (array $customFunctions) {
            return array_merge($customFunctions, $this->customFunctions);
        });

        $container->extend('forumkit.less.custom_variables', function (array $lessVariables) {
            return array_merge($this->lessVariables, $lessVariables);
        });

        $container->extend('forumkit.assets.factory', function (callable $factory) {
            return function (...$args) use ($factory) {
                /** @var Assets $assets */
                $assets = $factory(...$args);

                $assets->addLessImportOverrides($this->lessImportOverrides);
                $assets->addFileSourceOverrides($this->fileSourceOverrides);

                return $assets;
            };
        });
    }
}
