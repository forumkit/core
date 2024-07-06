<?php

namespace Forumkit\Extend;

use Forumkit\Api\Controller\AbstractSerializeController;
use Forumkit\Extension\Extension;
use Forumkit\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class ApiController implements ExtenderInterface
{
    private $controllerClass;
    private $beforeDataCallbacks = [];
    private $beforeSerializationCallbacks = [];
    private $serializer;
    private $addIncludes = [];
    private $removeIncludes = [];
    private $addOptionalIncludes = [];
    private $removeOptionalIncludes = [];
    private $limit;
    private $maxLimit;
    private $addSortFields = [];
    private $removeSortFields = [];
    private $sort;
    private $load = [];
    private $loadCallables = [];

    /**
     * @param string $controllerClass: 要修改的控制器的  ::class 属性。此控制器应从 \Forumkit\Api\Controller\AbstractSerializeController 扩展。
     */
    public function __construct(string $controllerClass)
    {
        $this->controllerClass = $controllerClass;
    }

    /**
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     *
     * @return self
     */
    public function prepareDataQuery($callback): self
    {
        $this->beforeDataCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param callable|string $callback
     *
     * 回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     * - $data: 混合类型，可以是数据数组或对象（如Collection或AbstractModel的实例）。
     * - $request: \Psr\Http\Message\ServerRequestInterface 的实例
     * - $document: \Tobscure\JsonApi\Document 的实例
     *
     * 回调函数应返回：
     * - 与现有数组合并的额外数据数组。
     *   或已修改的$data数组。
     *
     * @return self
     */
    public function prepareDataForSerialization($callback): self
    {
        $this->beforeSerializationCallbacks[] = $callback;

        return $this;
    }

    /**
     * 设置将用于端点数据序列化的序列化器。
     *
     * @param string $serializerClass: 序列化器的::class属性
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值，以确定是否适用
     *
     * @return self
     */
    public function setSerializer(string $serializerClass, $callback = null): self
    {
        $this->serializer = [$serializerClass, $callback];

        return $this;
    }

    /**
     * 默认包含给定的关系。
     *
     * @param string|array $name: 关系的名称
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值，以确定是否适用。
     *
     * @return self
     */
    public function addInclude($name, $callback = null): self
    {
        $this->addIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * 默认不包含给定的关系。
     *
     * @param string|array $name: 关系的名称
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值，以确定是否适用。
     *
     * @return self
     */
    public function removeInclude($name, $callback = null): self
    {
        $this->removeIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * 使给定的关系可用于包含。
     *
     * @param string|array $name: 关系的名称
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值，以确定是否适用。
     *
     * @return self
     */
    public function addOptionalInclude($name, $callback = null): self
    {
        $this->addOptionalIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * 不允许包含给定的关系。
     *
     * @param string|array $name: 关系的名称
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 该控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值，以确定是否适用。
     *
     * @return self
     */
    public function removeOptionalInclude($name, $callback = null): self
    {
        $this->removeOptionalIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * 设置默认结果数量。
     *
     * @param int $limit
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 此控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值以确定是否应用此设置
     *
     * @return self
     */
    public function setLimit(int $limit, $callback = null): self
    {
        $this->limit = [$limit, $callback];

        return $this;
    }

    /**
     * 设置最大结果数量。
     *
     * @param int $max
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 此控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值以确定是否应用此设置
     *
     * @return self
     */
    public function setMaxLimit(int $max, $callback = null): self
    {
        $this->maxLimit = [$max, $callback];

        return $this;
    }

    /**
     * 允许根据给定字段对结果进行排序。
     *
     * @param string|array $field
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 此控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值以确定是否应用此设置
     *
     * @return self
     */
    public function addSortField($field, $callback = null): self
    {
        $this->addSortFields[] = [$field, $callback];

        return $this;
    }

    /**
     * 禁止根据给定字段对结果进行排序。
     *
     * @param string|array $field
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 此控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值以确定是否应用此设置
     *
     * @return self
     */
    public function removeSortField($field, $callback = null): self
    {
        $this->removeSortFields[] = [$field, $callback];

        return $this;
    }

    /**
     * 设置结果的默认排序顺序。
     *
     * @param array $sort
     * @param callable|string|null $callback
     *
     * 可选的回调函数可以是一个闭包或可调用类，并应接受以下参数：
     * - $controller: 此控制器的实例
     *
     * 回调函数应返回：
     * - 一个布尔值以确定是否应用此设置
     *
     * @return self
     */
    public function setSort(array $sort, $callback = null): self
    {
        $this->sort = [$sort, $callback];

        return $this;
    }

    /**
     * 预先加载序列化逻辑所需的关联关系。
     *
     * 无论是否包含在响应中，都会加载第一级关联关系。
     * 只有当上层关联关系被包含或手动加载时，才会加载子级关联关系。
     *
     * @example 如果指定了如 'relation.subRelation' 这样的关联关系
     * 它只会在 'relation' 被加载或被手动加载时才会被加载
     * 要强制加载关联关系，必须指定所有层级
     * 例如: ['relation', 'relation.subRelation'].
     *
     * @param string|string[] $relations
     * @return self
     */
    public function load($relations): self
    {
        $this->load = array_merge($this->load, array_map('strval', (array) $relations));

        return $this;
    }

    /**
     * 允许使用额外的查询修改来加载关联关系。
     *
     * @param string $relation: 关联关系名称，参见load方法说明
     * @param array|(callable(\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Relations\Relation, \Psr\Http\Message\ServerRequestInterface|null, array): void) $callback
     *
     * The callback to modify the query, should accept:
     * - \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query: A query object.
     * - \Psr\Http\Message\ServerRequestInterface|null $request: An instance of the request.
     * - array $relations: An array of relations that are to be loaded.
     *
     * @return self
     */
    public function loadWhere(string $relation, callable $callback): self // @phpstan-ignore-line
    {
        $this->loadCallables = array_merge($this->loadCallables, [$relation => $callback]);

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $this->beforeDataCallbacks[] = function (AbstractSerializeController $controller) use ($container) {
            if (isset($this->serializer) && $this->isApplicable($this->serializer[1], $controller, $container)) {
                $controller->setSerializer($this->serializer[0]);
            }

            foreach ($this->addIncludes as $addingInclude) {
                if ($this->isApplicable($addingInclude[1], $controller, $container)) {
                    $controller->addInclude($addingInclude[0]);
                }
            }

            foreach ($this->removeIncludes as $removingInclude) {
                if ($this->isApplicable($removingInclude[1], $controller, $container)) {
                    $controller->removeInclude($removingInclude[0]);
                }
            }

            foreach ($this->addOptionalIncludes as $addingOptionalInclude) {
                if ($this->isApplicable($addingOptionalInclude[1], $controller, $container)) {
                    $controller->addOptionalInclude($addingOptionalInclude[0]);
                }
            }

            foreach ($this->removeOptionalIncludes as $removingOptionalInclude) {
                if ($this->isApplicable($removingOptionalInclude[1], $controller, $container)) {
                    $controller->removeOptionalInclude($removingOptionalInclude[0]);
                }
            }

            foreach ($this->addSortFields as $addingSortField) {
                if ($this->isApplicable($addingSortField[1], $controller, $container)) {
                    $controller->addSortField($addingSortField[0]);
                }
            }

            foreach ($this->removeSortFields as $removingSortField) {
                if ($this->isApplicable($removingSortField[1], $controller, $container)) {
                    $controller->removeSortField($removingSortField[0]);
                }
            }

            if (isset($this->limit) && $this->isApplicable($this->limit[1], $controller, $container)) {
                $controller->setLimit($this->limit[0]);
            }

            if (isset($this->maxLimit) && $this->isApplicable($this->maxLimit[1], $controller, $container)) {
                $controller->setMaxLimit($this->maxLimit[0]);
            }

            if (isset($this->sort) && $this->isApplicable($this->sort[1], $controller, $container)) {
                $controller->setSort($this->sort[0]);
            }
        };

        foreach ($this->beforeDataCallbacks as $beforeDataCallback) {
            $beforeDataCallback = ContainerUtil::wrapCallback($beforeDataCallback, $container);
            AbstractSerializeController::addDataPreparationCallback($this->controllerClass, $beforeDataCallback);
        }

        foreach ($this->beforeSerializationCallbacks as $beforeSerializationCallback) {
            $beforeSerializationCallback = ContainerUtil::wrapCallback($beforeSerializationCallback, $container);
            AbstractSerializeController::addSerializationPreparationCallback($this->controllerClass, $beforeSerializationCallback);
        }

        AbstractSerializeController::setLoadRelations($this->controllerClass, $this->load);
        AbstractSerializeController::setLoadRelationCallables($this->controllerClass, $this->loadCallables);
    }

    /**
     * @param callable|string|null $callback
     * @param AbstractSerializeController $controller
     * @param Container $container
     * @return bool
     */
    private function isApplicable($callback, AbstractSerializeController $controller, Container $container)
    {
        if (! isset($callback)) {
            return true;
        }

        $callback = ContainerUtil::wrapCallback($callback, $container);

        return (bool) $callback($controller);
    }
}
