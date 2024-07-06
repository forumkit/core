<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Auth implements ExtenderInterface
{
    private $addPasswordCheckers = [];
    private $removePasswordCheckers = [];

    /**
     * 添加一个新的密码检查器。
     *
     * @param string $identifier: 密码检查器的唯一标识符。
     * @param callable|string $callback: 包含密码检查器逻辑的闭包或可调用类。
     *
     * 可调用函数应该接收以下参数：
     * - $user: User模型的实例
     * - $password: 字符串
     *
     * 可调用函数应该返回：
     * - 如果给定的密码有效，则返回`true`。
     * - 如果给定的密码无效，或者此检查器不适用，则返回`null`（或什么也不返回）。
     *           一般来说，应该返回`null`而不是`false`，以便其他密码检查器可以运行。
     * - 如果给定的密码无效，并且不应考虑其他检查器，则返回`false`。
     *            如果任何检查器返回`false`，则评估将立即停止。
     *
     * @return self
     */
    public function addPasswordChecker(string $identifier, $callback): self
    {
        $this->addPasswordCheckers[$identifier] = $callback;

        return $this;
    }

    /**
     * 移除一个密码检查器。
     *
     * @param string $identifier: 要移除的密码检查器的唯一标识符。
     * @return self
     */
    public function removePasswordChecker(string $identifier): self
    {
        $this->removePasswordCheckers[] = $identifier;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.user.password_checkers', function ($passwordCheckers) use ($container) {
            foreach ($this->removePasswordCheckers as $identifier) {
                if (array_key_exists($identifier, $passwordCheckers)) {
                    unset($passwordCheckers[$identifier]);
                }
            }

            foreach ($this->addPasswordCheckers as $identifier => $checker) {
                $passwordCheckers[$identifier] = ContainerUtil::wrapCallback($checker, $container);
            }

            return $passwordCheckers;
        });
    }
}
