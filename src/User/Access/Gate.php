<?php

namespace Forumkit\User\Access;

use Forumkit\Database\AbstractModel;
use Forumkit\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class Gate
{
    protected const EVALUATION_CRITERIA_PRIORITY = [
        AbstractPolicy::FORCE_DENY => false,
        AbstractPolicy::FORCE_ALLOW => true,
        AbstractPolicy::DENY => false,
        AbstractPolicy::ALLOW => true,
    ];

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $policyClasses;

    /**
     * @var array
     */
    protected $policies;

    /**
     * @param Container $container
     * @param array $policyClasses
     */
    public function __construct(Container $container, array $policyClasses)
    {
        $this->container = $container;
        $this->policyClasses = $policyClasses;
    }

    /**
     * 确定是否应为当前用户授予给定的能力。
     *
     * @param  User $actor
     * @param  string  $ability
     * @param  string|AbstractModel $model
     * @return bool
     */
    public function allows(User $actor, string $ability, $model): bool
    {
        $results = [];
        $appliedPolicies = [];

        if ($model) {
            $modelClasses = is_string($model) ? [$model] : array_merge(class_parents($model), [get_class($model)]);

            foreach ($modelClasses as $class) {
                $appliedPolicies = array_merge($appliedPolicies, $this->getPolicies($class));
            }
        } else {
            $appliedPolicies = $this->getPolicies(AbstractPolicy::GLOBAL);
        }

        foreach ($appliedPolicies as $policy) {
            $results[] = $policy->checkAbility($actor, $ability, $model);
        }

        foreach (static::EVALUATION_CRITERIA_PRIORITY as $criteria => $decision) {
            if (in_array($criteria, $results, true)) {
                return $decision;
            }
        }

        // 如果没有任何策略涵盖了这个权限查询，我们只会在行为者的组拥有该权限时授予权限。
        // 否则，我们将不允许用户执行这个操作。
        if ($actor->isAdmin() || $actor->hasPermission($ability)) {
            return true;
        }

        return false;
    }

    /**
     * 获取给定模型和能力的所有策略。
     */
    protected function getPolicies(string $model)
    {
        $compiledPolicies = Arr::get($this->policies, $model);
        if (is_null($compiledPolicies)) {
            $policyClasses = Arr::get($this->policyClasses, $model, []);
            $compiledPolicies = array_map(function ($policyClass) {
                return $this->container->make($policyClass);
            }, $policyClasses);
            Arr::set($this->policies, $model, $compiledPolicies);
        }

        return $compiledPolicies;
    }
}
