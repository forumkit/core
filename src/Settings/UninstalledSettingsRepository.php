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
        // Do nothing
    }

    public function delete($keyLike)
    {
        // Do nothing
    }
}
