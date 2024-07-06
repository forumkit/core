<?php

namespace Forumkit\Settings;

use Illuminate\Support\Collection;

class DefaultSettingsRepository implements SettingsRepositoryInterface
{
    protected $defaults;

    private $inner;

    public function __construct(SettingsRepositoryInterface $inner, Collection $defaults)
    {
        $this->inner = $inner;
        $this->defaults = $defaults;
    }

    public function get($key, $default = null)
    {
        // 全局默认设置会覆盖局部默认设置，因为局部默认设置已被弃用，
        // 并将在2.0版本中移除
        return $this->inner->get($key, $this->defaults->get($key, $default));
    }

    public function set($key, $value)
    {
        $this->inner->set($key, $value);
    }

    public function delete($keyLike)
    {
        $this->inner->delete($keyLike);
    }

    public function all(): array
    {
        return array_merge($this->defaults->toArray(), $this->inner->all());
    }
}
