<?php

namespace Forumkit\Settings;

class UninstalledSettingsRepository implements SettingsRepositoryInterface
{
    public function all(): array
    {
        return [];
    }

    public function get($key, $default = null)
    {
        return $default;
    }

    public function set($key, $value)
    {
        // 不执行任何操作
    }

    public function delete($keyLike)
    {
        // 不执行任何操作
    }
}
