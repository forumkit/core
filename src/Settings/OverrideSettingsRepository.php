<?php

namespace Forumkit\Settings;

use Illuminate\Support\Arr;

/**
 * 一个设置仓库装饰器，允许覆盖某些值。
 *
 * `OverrideSettingsRepository` 类装饰了另一个 `SettingsRepositoryInterface` 实例，
 * 但允许使用预定义的值覆盖某些设置。它不会影响写入方法。
 *
 * 在 Forumkit 中，这可以用于在将新设置值提交到数据库之前，在系统中测试这些值。
 *
 * @see \Forumkit\Site\ValidateCustomLess 示例用法。
 */
class OverrideSettingsRepository implements SettingsRepositoryInterface
{
    protected $inner;

    protected $overrides = [];

    public function __construct(SettingsRepositoryInterface $inner, array $overrides)
    {
        $this->inner = $inner;
        $this->overrides = $overrides;
    }

    public function all(): array
    {
        return array_merge($this->inner->all(), $this->overrides);
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->overrides)) {
            return $this->overrides[$key];
        }

        return Arr::get($this->all(), $key, $default);
    }

    public function set($key, $value)
    {
        $this->inner->set($key, $value);
    }

    public function delete($key)
    {
        $this->inner->delete($key);
    }
}
