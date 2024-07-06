<?php

namespace Forumkit\Extend;

use Forumkit\Api\Serializer\AbstractSerializer;
use Forumkit\Api\Serializer\SiteSerializer;
use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class Settings implements ExtenderInterface
{
    private $settings = [];
    private $defaults = [];
    private $lessConfigs = [];

    /**
     * 将设置值序列化为SiteSerializer的属性。
     *
     * @param string $attributeName: 在SiteSerializer属性数组中使用的属性名。
     * @param string $key: 设置的键
     * @param string|callable|null $callback: 序列化前可选的回调函数来修改值。
     *
     * 回调函数可以是一个闭包或可调用类，并应接受：
     * - mixed $value: 设置的值
     *
     * 可调用函数应返回：
     * - mixed $value: 修改后的值
     *
     * @todo 在2.0版本中移除 $default
     * @param mixed $default: 已弃用的可选默认序列化值。将通过可选的回调函数运行。
     * @return self 返回当前实例对象
     */
    public function serializeToSite(string $attributeName, string $key, $callback = null, $default = null): self
    {
        $this->settings[$key] = compact('attributeName', 'callback', 'default');

        return $this;
    }

    /**
     * 为设置设置一个默认值。
     * 用迁移替换插入默认值。
     *
     * @param string $key: 设置的键，必须是唯一的。使用扩展ID进行命名（例如：'my-extension-id.setting_key'）。
     * @param mixed $value: 设置的值
     * @return self 返回当前实例对象
     */
    public function default(string $key, $value): self
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * 将设置注册为LESS配置变量。
     *
     * @param string $configName: 配置变量的名称，使用连字符格式。
     * @param string $key: 设置的键。
     * @param string|callable|null $callback: 可选的回调函数来修改值。
     *
     * 回调函数可以是一个闭包或可调用类，并应接受：
     * - mixed $value: 设置的值
     *
     * 可调用函数应返回：
     * - mixed $value: 修改后的值
     *
     * @return self 返回当前实例对象
     */
    public function registerLessConfigVar(string $configName, string $key, $callback = null): self
    {
        $this->lessConfigs[$configName] = compact('key', 'callback');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->defaults)) {
            $container->extend('forumkit.settings.default', function (Collection $defaults) {
                foreach ($this->defaults as $key => $value) {
                    if ($defaults->has($key)) {
                        throw new \RuntimeException("无法修改不可变的默认设置 $key.");
                    }

                    $defaults->put($key, $value);
                }

                return $defaults;
            });
        }

        if (! empty($this->settings)) {
            AbstractSerializer::addAttributeMutator(
                SiteSerializer::class,
                function () use ($container) {
                    $settings = $container->make(SettingsRepositoryInterface::class);
                    $attributes = [];

                    foreach ($this->settings as $key => $setting) {
                        $value = $settings->get($key, $setting['default']);

                        if (isset($setting['callback'])) {
                            $callback = ContainerUtil::wrapCallback($setting['callback'], $container);
                            $value = $callback($value);
                        }

                        $attributes[$setting['attributeName']] = $value;
                    }

                    return $attributes;
                }
            );
        }

        if (! empty($this->lessConfigs)) {
            $container->extend('forumkit.less.config', function (array $existingConfig, Container $container) {
                $config = $this->lessConfigs;

                foreach ($config as $var => $data) {
                    if (isset($data['callback'])) {
                        $config[$var]['callback'] = ContainerUtil::wrapCallback($data['callback'], $container);
                    }
                }

                return array_merge($existingConfig, $config);
            });
        }
    }
}
