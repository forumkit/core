<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Extension\ExtensionManager;
use Illuminate\Contracts\Container\Container;

/**
 * 条件扩展器允许开发者根据布尔值或可调用函数的结果有条件地应用其他扩展器。
 *
 * 这对于仅在满足某些条件（如启用特定扩展或特定配置设置）时应用扩展器很有用。
 */
class Conditional implements ExtenderInterface
{
    /**
     * 条件及其关联扩展器的数组。
     *
     * 每个条目应该包含：
     * - 'condition': 一个布尔值或可返回布尔值的可调用函数。
     * - 'extenders': 扩展器数组、返回扩展器数组的可调用函数或可调用的类字符串。
     *
     * @var array<array{condition: bool|callable, extenders: ExtenderInterface[]|callable|string}>
     */
    protected $conditions = [];

    /**
     * 仅在启用特定扩展时应用扩展器。
     *
     * @param string $extensionId 扩展的ID
     * @param ExtenderInterface[]|callable|string $extenders 扩展器数组、返回扩展器数组的可调用函数或可调用的类字符串。
     * @return self
     */
    public function whenExtensionEnabled(string $extensionId, $extenders): self
    {
        return $this->when(function (ExtensionManager $extensions) use ($extensionId) {
            return $extensions->isEnabled($extensionId);
        }, $extenders);
    }

    /**
     * 根据条件应用扩展器。
     *
     * @param bool|callable $condition 一个布尔值或可返回布尔值的可调用函数。
     *                                 如果评估为true，将应用扩展器。
     * @param ExtenderInterface[]|callable|string $extenders 扩展器数组、返回扩展器数组的可调用函数或可调用的类字符串。
     * @return self
     */
    public function when($condition, $extenders): self
    {
        $this->conditions[] = [
            'condition' => $condition,
            'extenders' => $extenders,
        ];

        return $this;
    }

    /**
     * 遍历条件，如果条件满足，则应用相关的扩展器。
     *
     * @param Container $container
     * @param Extension|null $extension
     * @return void
     */
    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->conditions as $condition) {
            if (is_callable($condition['condition'])) {
                $condition['condition'] = $container->call($condition['condition']);
            }

            if ($condition['condition']) {
                $extenders = $condition['extenders'];

                if (is_string($extenders) || is_callable($extenders)) {
                    $extenders = $container->call($extenders);
                }

                foreach ($extenders as $extender) {
                    $extender->extend($container, $extension);
                }
            }
        }
    }
}
