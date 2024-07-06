<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\User\Access\AbstractPolicy;
use Illuminate\Contracts\Container\Container;

class Policy implements ExtenderInterface
{
    private $globalPolicies = [];
    private $modelPolicies = [];

    /**
     * 添加一个自定义策略，用于在没有模型实例的情况下执行权限检查。
     *
     * @param string $policy: 策略类的::class属性，该类必须扩展自 Forumkit\User\Access\AbstractPolicy
     * @return self
     */
    public function globalPolicy(string $policy): self
    {
        $this->globalPolicies[] = $policy;

        return $this;
    }

    /**
     * 为模型实例添加自定义策略，用于在该模型实例上执行权限检查。
     *
     * @param string $modelClass: 应用策略的模型的::class属性 该模型应该继承自 \Forumkit\Database\AbstractModel
     * @param string $policy: 策略类的::class属性，该类必须扩展自 Forumkit\User\Access\AbstractPolicy
     * @return self
     */
    public function modelPolicy(string $modelClass, string $policy): self
    {
        if (! array_key_exists($modelClass, $this->modelPolicies)) {
            $this->modelPolicies[$modelClass] = [];
        }

        $this->modelPolicies[$modelClass][] = $policy;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('forumkit.policies', function ($existingPolicies) {
            foreach ($this->modelPolicies as $modelClass => $addPolicies) {
                if (! array_key_exists($modelClass, $existingPolicies)) {
                    $existingPolicies[$modelClass] = [];
                }

                foreach ($addPolicies as $policy) {
                    $existingPolicies[$modelClass][] = $policy;
                }
            }

            $existingPolicies[AbstractPolicy::GLOBAL] = array_merge($existingPolicies[AbstractPolicy::GLOBAL], $this->globalPolicies);

            return $existingPolicies;
        });
    }
}
