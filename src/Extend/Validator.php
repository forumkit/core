<?php

namespace Forumkit\Extend;

use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Validator implements ExtenderInterface
{
    private $configurationCallbacks = [];
    private $validator;

    /**
     * @param string $validatorClass: 要修改的验证器的 ::class 属性。
     *                                验证器应该继承自 \Forumkit\Foundation\AbstractValidator
     */
    public function __construct(string $validatorClass)
    {
        $this->validator = $validatorClass;
    }

    /**
     * 配置验证器。这通常用于调整验证规则，但也可以用于对验证器进行其他更改。
     *
     * @param callable|class-string $callback 回调函数
     *
     * 回调函数可以是一个闭包或可调用类，并应该接受以下参数：
     * - \Forumkit\Foundation\AbstractValidator $forumkitValidator: Forumkit 验证器包装器
     * - \Illuminate\Validation\Validator $validator: Laravel 验证器实例
     *
     * 回调函数应该返回 void（即没有返回值）
     *
     * @return self
     */
    public function configure($callback): self
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving($this->validator, function ($validator, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                $validator->addConfiguration(ContainerUtil::wrapCallback($callback, $container));
            }
        });
    }
}
