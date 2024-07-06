<?php

namespace Forumkit\Foundation;

use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractValidator
{
    /**
     * @var array
     */
    protected $configuration = [];

    public function addConfiguration($callable)
    {
        $this->configuration[] = $callable;
    }

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Factory $validator
     * @param TranslatorInterface $translator
     */
    public function __construct(Factory $validator, TranslatorInterface $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
    }

    /**
     * 如果模型无效，则引发异常。
     *
     * @param array $attributes
     */
    public function assertValid(array $attributes)
    {
        $validator = $this->makeValidator($attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    protected function getMessages()
    {
        return [];
    }

    /**
     * 为此模型创建一个新的验证器实例。
     *
     * @param array $attributes
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(array $attributes)
    {
        $rules = Arr::only($this->getRules(), array_keys($attributes));

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
