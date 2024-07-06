<?php

namespace Forumkit\Settings;

use Forumkit\Foundation\AbstractValidator;

class SettingsValidator extends AbstractValidator
{
    /**
     * 这些规则适用于所有属性。
     *
     * 默认数据库设置表中的条目限制为 65,000 个字符。
     * 我们对此进行验证，以避免混淆的错误。
     *
     * @var array
     */
    protected $globalRules = [
        'max:65000',
    ];

    /**
     * 为此模型创建一个新的验证器实例。
     *
     * @param array $attributes
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(array $attributes)
    {
        // 首先应用全局规则。
        $rules = array_map(function () {
            return $this->globalRules;
        }, $attributes);

        // 应用特定于属性的规则。
        foreach ($rules as $key => $value) {
            $rules[$key] = array_merge($rules[$key], $this->rules[$key] ?? []);
        }

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
