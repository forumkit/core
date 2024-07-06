<?php

namespace Forumkit\Extend;

use Forumkit\Api\Serializer\AbstractSerializer;
use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class ApiSerializer implements ExtenderInterface
{
    private $serializerClass;
    private $attribute = [];
    private $attributes = [];
    private $relationships = [];

    /**
     * @param string $serializerClass 您要修改的序列化器的::class属性。
     *                                该序列化器应继承自 \Forumkit\Api\Serializer\AbstractSerializer.
     */
    public function __construct(string $serializerClass)
    {
        $this->serializerClass = $serializerClass;
    }

    /**
     * 向此序列化器添加一个属性。
     *
     * @param string $name: 属性的名称
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $serializer: 此序列化器的实例
     * - $model: 正在被序列化的模型的实例
     * - $attributes: 现有属性的数组
     *
     * 回调函数应返回：
     * - 属性的值
     *
     * @return self
     */
    public function attribute(string $name, $callback): self
    {
        $this->attribute[$name] = $callback;

        return $this;
    }

    /**
     * 向此序列化器的属性数组添加或修改属性。
     *
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $serializer: 此序列化器的实例
     * - $model: 正在被序列化的模型的实例
     * - $attributes: 现有属性的数组
     *
     * 回调函数应返回：
     * - 与现有数组合并的额外属性数组
     *   或已修改的$attributes数组
     *
     * @return self
     */
    public function attributes($callback): self
    {
        $this->attributes[] = $callback;

        return $this;
    }

    /**
     * 在此序列化器与另一个序列化器之间建立简单的hasOne关系。
     * 这表示一对一的关系。
     *
     * @param string $name: 关系的名称。必须与其他关系名称唯一。
     *                      该关系必须存在于由此序列化器处理的模型中。
     * @param string $serializerClass: 处理此关系的序列化器的::class属性。
     *                                 该序列化器应继承自 \Forumkit\Api\Serializer\AbstractSerializer.
     * @return self
     */
    public function hasOne(string $name, string $serializerClass): self
    {
        return $this->relationship($name, function (AbstractSerializer $serializer, $model) use ($serializerClass, $name) {
            return $serializer->hasOne($model, $serializerClass, $name);
        });
    }

    /**
     * 在此序列化器与另一个序列化器之间建立简单的hasMany关系。
     * 这表示一对多的关系。
     *
     * @param string $name: 关系的名称。必须与其他关系名称唯一。
     *                      该关系必须存在于由此序列化器处理的模型中。
     * @param string $serializerClass: 处理此关系的序列化器的::class属性。
     *                                 该序列化器应继承自 \Forumkit\Api\Serializer\AbstractSerializer.
     * @return self
     */
    public function hasMany(string $name, string $serializerClass): self
    {
        return $this->relationship($name, function (AbstractSerializer $serializer, $model) use ($serializerClass, $name) {
            return $serializer->hasMany($model, $serializerClass, $name);
        });
    }

    /**
     * 从此序列化器到另一个序列化器添加关系。
     *
     * @param string $name: 关系的名称。必须与其他关系名称唯一。
     *                      该关系必须存在于由此序列化器处理的模型中。
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $serializer: 此序列化器的实例
     * - $model: 正在被序列化的模型的实例
     *
     * 回调函数应返回：
     * - $relationship: \Tobscure\JsonApi\Relationship 的实例
     *
     * @return self
     */
    public function relationship(string $name, $callback): self
    {
        $this->relationships[$this->serializerClass][$name] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->attribute)) {
            $this->attributes[] = function ($serializer, $model, $attributes) use ($container) {
                foreach ($this->attribute as $attributeName => $callback) {
                    $callback = ContainerUtil::wrapCallback($callback, $container);

                    $attributes[$attributeName] = $callback($serializer, $model, $attributes);
                }

                return $attributes;
            };
        }

        foreach ($this->attributes as $callback) {
            $callback = ContainerUtil::wrapCallback($callback, $container);

            AbstractSerializer::addAttributeMutator($this->serializerClass, $callback);
        }

        foreach ($this->relationships as $serializerClass => $relationships) {
            foreach ($relationships as $relation => $callback) {
                $callback = ContainerUtil::wrapCallback($callback, $container);

                AbstractSerializer::setRelationship($serializerClass, $relation, $callback);
            }
        }
    }
}
