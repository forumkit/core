<?php

namespace Forumkit\User\Access;

use Forumkit\User\User;

abstract class AbstractPolicy
{
    public const GLOBAL = 'GLOBAL';
    public const ALLOW = 'ALLOW';
    public const DENY = 'DENY';
    public const FORCE_ALLOW = 'FORCE_ALLOW';
    public const FORCE_DENY = 'FORCE_DENY';

    protected function allow()
    {
        return static::ALLOW;
    }

    protected function deny()
    {
        return static::DENY;
    }

    protected function forceAllow()
    {
        return static::FORCE_ALLOW;
    }

    protected function forceDeny()
    {
        return static::FORCE_DENY;
    }

    /**
     * @return string|void
     */
    public function checkAbility(User $actor, string $ability, $instance)
    {
        // 如果为这个权限定义了一个特定的方法，
        // 调用该方法并返回任何非空结果
        if (method_exists($this, $ability)) {
            $result = $this->sanitizeResult(call_user_func_array([$this, $ability], [$actor, $instance]));

            if (! is_null($result)) {
                return $result;
            }
        }

        // 如果定义了“完全访问”方法，尝试调用它。
        if (method_exists($this, 'can')) {
            return $this->sanitizeResult(call_user_func_array([$this, 'can'], [$actor, $ability, $instance]));
        }
    }

    /**
     * 允许使用 `true` 代替 `->allow()`，以及使用 `false` 代替 `->deny()`
     * 这允许使用更简洁和直观的代码，通过返回布尔语句来实现：
     *
     * 不使用此方法：
     * `return SOME_BOOLEAN_LOGIC ? $this->allow() : $this->deny();
     *
     * 使用此方法：
     * `return SOME_BOOLEAN_LOGIC;
     *
     * @param mixed $result
     * @return string|void|null
     */
    public function sanitizeResult($result)
    {
        if ($result === true) {
            return $this->allow();
        } elseif ($result === false) {
            return $this->deny();
        }

        return $result;
    }
}
