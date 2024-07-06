<?php

namespace Forumkit\Extend;

use Forumkit\Database\AbstractModel;
use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class Model implements ExtenderInterface
{
    private $modelClass;
    private $customRelations = [];
    private $casts = [];

    /**
     * @param string $modelClass: 要修改的模型的 ::class 属性。
     *                           该模型应该继承自 \Forumkit\Database\AbstractModel
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * 添加一个被视为日期的属性。
     *
     * @param string $attribute 属性名
     * @return self 返回当前实例（支持链式调用）
     * @deprecated 请使用 `cast` 方法代替。将在 v2 版本中移除。
     */
    public function dateAttribute(string $attribute): self
    {
        $this->cast($attribute, 'datetime');

        return $this;
    }

    /**
     * 添加自定义属性类型转换。不应应用于非扩展属性。
     *
     * @param string $attribute: 新的属性名
     * @param string $cast: 转换类型。参见 https://laravel.com/docs/8.x/eloquent-mutators#attribute-casting
     * @return self
     */
    public function cast(string $attribute, string $cast): self
    {
        $this->casts[$attribute] = $cast;

        return $this;
    }

    /**
     * 为给定的属性添加默认值，该值可以是明确的值、闭包或可调用类的实例。
     * 与其他一些扩展器不同，它不能是可调用类的 `::class` 属性。
     *
     * @param string $attribute
     * @param mixed $value
     * @return self
     */
    public function default(string $attribute, $value): self
    {
        Arr::set(AbstractModel::$defaults, "$this->modelClass.$attribute", $value);

        return $this;
    }

    /**
     * 建立从当前模型到另一个模型的简单 belongsTo 关系。
     * 这表示反向的一对一或反向的一对多关系。
     * 对于更复杂的关系，请使用 ->relationship 方法。
     *
     * @param string $name: 关系的名称。不必是特定的，但必须对此模型的其他关系名称是唯一的，并且应该可以用作方法名。
     * @param string $related: 模型的 ::class 属性，应该继承自 \Forumkit\Database\AbstractModel.
     * @param string $foreignKey: 父模型的外键属性
     * @param string $ownerKey: 父模型的主键属性
     * @return self
     */
    public function belongsTo(string $name, string $related, string $foreignKey = null, string $ownerKey = null): self
    {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $foreignKey, $ownerKey, $name) {
            return $model->belongsTo($related, $foreignKey, $ownerKey, $name);
        });
    }

    /**
     * 在此模型与另一个模型之间建立简单的 belongsToMany 关联。
     * 这表示多对多关系。
     * 对于更复杂的关联，请使用 ->relationship 方法
     *
     * @param string $name: 关联的名称。这不必是特定的名称
     *                      但必须对于此模型的其他关联名称是唯一的，并且应该可以用作方法名
     * @param string $related: 模型的 ::class 属性，它应该继承自 \Forumkit\Database\AbstractModel.
     * @param string $table: 此关联的中间表
     * @param string $foreignPivotKey: 父模型的外键属性
     * @param string $relatedPivotKey: 关联的关联键属性
     * @param string $parentKey: 父模型的键名
     * @param string $relatedKey: 相关模型的键名
     * @return self
     */
    public function belongsToMany(
        string $name,
        string $related,
        string $table = null,
        string $foreignPivotKey = null,
        string $relatedPivotKey = null,
        string $parentKey = null,
        string $relatedKey = null
    ): self {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $name) {
            return $model->belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $name);
        });
    }

    /**
     * 从当前模型到另一个模型建立简单的 hasOne 关联。
     * 这表示一对一关系。
     * 对于更复杂的关联，请使用 ->relationship 方法。
     *
     * @param string $name: 关联的名称。这不必是特定的名称
     *                     但必须对于此模型的其他关联名称是唯一的，并且应该可以用作方法名
     * @param string $related: 模型的 ::class 属性，它应该继承自 \Forumkit\Database\AbstractModel
     * @param string $foreignKey: 父模型的外键属性
     * @param string $localKey: 父模型的主键属性
     * @return self
     */
    public function hasOne(string $name, string $related, string $foreignKey = null, string $localKey = null): self
    {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $foreignKey, $localKey) {
            return $model->hasOne($related, $foreignKey, $localKey);
        });
    }

    /**
     * 在此模型与另一个模型之间建立简单的 hasMany 关联。
     * 这表示一对多关系。
     *  对于更复杂的关联，请使用 ->relationship 方法。
     *
     * @param string $name: 关联的名称。不必是特定的名称
     *                      但必须对于此模型的其他关联名称是唯一的，并且应该可以用作方法名
     * @param string $related: T模型的 ::class 属性，它应该继承自 \Forumkit\Database\AbstractModel.
     * @param string $foreignKey: 父模型的外键属性
     * @param string $localKey: 父模型的主键属性
     * @return self
     */
    public function hasMany(string $name, string $related, string $foreignKey = null, string $localKey = null): self
    {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $foreignKey, $localKey) {
            return $model->hasMany($related, $foreignKey, $localKey);
        });
    }

    /**
     * 从当前模型到另一个模型添加关联。
     *
     * @param string $name: 关联的名称。不必是特定的名称
     *                      但必须对于此模型的其他关联名称是唯一的，并且应该可以用作方法名
     * @param callable|string $callback 回调函数或字符串
     *
     * 回调函数可以是闭包或可调用类，并且应该接受以下参数：
     * - $instance: 当前模型的实例
     *
     * 回调函数应该返回：
     * - $relationship: Laravel 的关联对象。查看模型的相关方法，如 \Forumkit\User\User 以获取如何返回关联的示例
     *
     * @return self
     */
    public function relationship(string $name, $callback): self
    {
        $this->customRelations[$name] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->customRelations as $name => $callback) {
            Arr::set(AbstractModel::$customRelations, "$this->modelClass.$name", ContainerUtil::wrapCallback($callback, $container));
        }

        Arr::set(
            AbstractModel::$customCasts,
            $this->modelClass,
            array_merge(
                Arr::get(AbstractModel::$customCasts, $this->modelClass, []),
                $this->casts
            )
        );
    }
}
