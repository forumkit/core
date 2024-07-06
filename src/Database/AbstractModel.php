<?php

namespace Forumkit\Database;

use Forumkit\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LogicException;

/**
 * 基于 Eloquent 的基础模型类。
 *
 * 在运行时，向模型中添加自定义关联的能力。
 * 这些关联的行为符合预期，可以进行查询、预加载和作为属性访问。
 *
 * @property-read int|null $id
 */
abstract class AbstractModel extends Eloquent
{
    /**
     * 指示模型是否应该被打上时间戳。默认关闭。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 在模型保存后运行一次的回调函数数组。
     *
     * @var callable[]
     */
    protected $afterSaveCallbacks = [];

    /**
     * 在模型删除后运行一次的回调函数数组。
     *
     * @var callable[]
     */
    protected $afterDeleteCallbacks = [];

    /**
     * @internal
     */
    public static $customRelations = [];

    /**
     * @internal
     */
    public static $customCasts = [];

    /**
     * @internal
     */
    public static $defaults = [];

    /**
     * 在查询中使用的表名别名。
     *
     * @var string|null
     * @internal
     */
    protected $tableAlias = null;

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        static::saved(function (self $model) {
            foreach ($model->releaseAfterSaveCallbacks() as $callback) {
                $callback($model);
            }
        });

        static::deleted(function (self $model) {
            foreach ($model->releaseAfterDeleteCallbacks() as $callback) {
                $callback($model);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = [];

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $this->attributes = array_merge($this->attributes, Arr::get(static::$defaults, $class, []));
        }

        $this->attributes = array_map(function ($item) {
            return is_callable($item) ? $item($this) : $item;
        }, $this->attributes);

        parent::__construct($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getCasts()
    {
        $casts = parent::getCasts();

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $casts = array_merge($casts, Arr::get(static::$customCasts, $class, []));
        }

        return $casts;
    }

    /**
     * 从模型中获取一个属性。如果找不到，则尝试加载使用此键的自定义关联方法。
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! is_null($value = parent::getAttribute($key))) {
            return $value;
        }

        // 如果已经为此键设置了自定义关联，那么我们将从查询中加载并返回结果，并将关联的值注入到 "relationships" 数组中。
        if (! $this->relationLoaded($key) && ($relation = $this->getCustomRelation($key))) {
            if (! $relation instanceof Relation) {
                throw new LogicException(
                    '关联方法必须返回一个类型为 '.Relation::class
                );
            }

            return $this->relations[$key] = $relation->getResults();
        }
    }

    /**
     * 获取一个自定义关联对象。
     *
     * @param string $name
     * @return mixed
     */
    protected function getCustomRelation($name)
    {
        foreach (array_merge([static::class], class_parents($this)) as $class) {
            $relation = Arr::get(static::$customRelations, $class.".$name", null);
            if (! is_null($relation)) {
                return $relation($this);
            }
        }
    }

    /**
     * 注册一个回调函数，该回调函数将在模型保存后运行一次。
     *
     * @param callable $callback
     * @return void
     */
    public function afterSave($callback)
    {
        $this->afterSaveCallbacks[] = $callback;
    }

    /**
     * 注册一个回调函数，该回调函数将在模型删除后运行一次。
     *
     * @param callable $callback
     * @return void
     */
    public function afterDelete($callback)
    {
        $this->afterDeleteCallbacks[] = $callback;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterSaveCallbacks()
    {
        $callbacks = $this->afterSaveCallbacks;

        $this->afterSaveCallbacks = [];

        return $callbacks;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterDeleteCallbacks()
    {
        $callbacks = $this->afterDeleteCallbacks;

        $this->afterDeleteCallbacks = [];

        return $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $arguments)
    {
        if ($relation = $this->getCustomRelation($method)) {
            return $relation;
        }

        return parent::__call($method, $arguments);
    }

    public function newModelQuery()
    {
        $query = parent::newModelQuery();

        if ($this->tableAlias) {
            $query->from($this->getTable().' as '.$this->tableAlias);
        }

        return $query;
    }

    public function qualifyColumn($column)
    {
        if (Str::contains($column, '.')) {
            return $column;
        }

        return ($this->tableAlias ?? $this->getTable()).'.'.$column;
    }

    public function withTableAlias(callable $callback)
    {
        static $aliasCount = 0;
        $this->tableAlias = 'forumkit_reserved_'.++$aliasCount;

        $result = $callback();

        $this->tableAlias = null;

        return $result;
    }

    /**
     * @param \Illuminate\Support\Collection|array $models
     */
    public function newCollection($models = [])
    {
        return new Collection($models);
    }
}
