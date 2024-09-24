<?php

namespace Forumkit\Api\Controller;

use Forumkit\Api\JsonApiResponse;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Parameters;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractSerializeController implements RequestHandlerInterface
{
    /**
     * 序列化器类名称，用于输出处理结果
     *
     * @var string
     */
    public $serializer;

    /**
     * 默认包含的关联关系数组
     *
     * @var array
     */
    public $include = [];

    /**
     * 可选包含的关联关系数组
     *
     * @var array
     */
    public $optionalInclude = [];

    /**
     * 可以请求的最大记录数
     *
     * @var int
     */
    public $maxLimit = 50;

    /**
     * 默认包含的记录数
     *
     * @var int
     */
    public $limit = 20;

    /**
     * 可用于排序的字段数组
     *
     * @var array
     */
    public $sortFields = [];

    /**
     * 默认使用的排序字段和顺序
     *
     * @var array|null
     */
    public $sort;

    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var array
     */
    protected static $beforeDataCallbacks = [];

    /**
     * @var array
     */
    protected static $beforeSerializationCallbacks = [];

    /**
     * @var string[][]
     */
    protected static $loadRelations = [];

    /**
     * @var array<string, callable>
     */
    protected static $loadRelationCallables = [];

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $document = new Document;

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$beforeDataCallbacks[$class])) {
                foreach (static::$beforeDataCallbacks[$class] as $callback) {
                    $callback($this);
                }
            }
        }

        $data = $this->data($request, $document);

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$beforeSerializationCallbacks[$class])) {
                foreach (static::$beforeSerializationCallbacks[$class] as $callback) {
                    $callback($this, $data, $request, $document);
                }
            }
        }

        if (empty($this->serializer)) {
            throw new InvalidArgumentException('Serializer required for controller: '.static::class);
        }

        $serializer = static::$container->make($this->serializer);
        $serializer->setRequest($request);

        $element = $this->createElement($data, $serializer)
            ->with($this->extractInclude($request))
            ->fields($this->extractFields($request));

        $document->setData($element);

        return new JsonApiResponse($document);
    }

    /**
     * 获取要序列化并分配给响应文档的数据
     *
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return mixed
     */
    abstract protected function data(ServerRequestInterface $request, Document $document);

    /**
     * 为输出在文档中创建一个PHP JSON-API元素
     *
     * @param mixed $data
     * @param SerializerInterface $serializer
     * @return \Tobscure\JsonApi\ElementInterface
     */
    abstract protected function createElement($data, SerializerInterface $serializer);

    /**
     * 返回由扩展器添加的要加载的关系
     *
     * @return string[]
     */
    protected function getRelationsToLoad(Collection $models): array
    {
        $addedRelations = [];

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$loadRelations[$class])) {
                $addedRelations = array_merge($addedRelations, static::$loadRelations[$class]);
            }
        }

        return $addedRelations;
    }

    /**
     * 返回由扩展器添加的要加载的关系可调用项
     *
     * @return array<string, callable>
     */
    protected function getRelationCallablesToLoad(Collection $models): array
    {
        $addedRelationCallables = [];

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$loadRelationCallables[$class])) {
                $addedRelationCallables = array_merge($addedRelationCallables, static::$loadRelationCallables[$class]);
            }
        }

        return $addedRelationCallables;
    }

    /**
     * 预加载所需的关系
     */
    protected function loadRelations(Collection $models, array $relations, ServerRequestInterface $request = null): void
    {
        $addedRelations = $this->getRelationsToLoad($models);
        $addedRelationCallables = $this->getRelationCallablesToLoad($models);

        foreach ($addedRelationCallables as $name => $relation) {
            $addedRelations[] = $name;
        }

        if (! empty($addedRelations)) {
            usort($addedRelations, function ($a, $b) {
                return substr_count($a, '.') - substr_count($b, '.');
            });

            foreach ($addedRelations as $relation) {
                if (strpos($relation, '.') !== false) {
                    $parentRelation = Str::beforeLast($relation, '.');

                    if (! in_array($parentRelation, $relations, true)) {
                        continue;
                    }
                }

                $relations[] = $relation;
            }
        }

        if (! empty($relations)) {
            $relations = array_unique($relations);
        }

        $callableRelations = [];
        $nonCallableRelations = [];

        foreach ($relations as $relation) {
            if (isset($addedRelationCallables[$relation])) {
                $load = $addedRelationCallables[$relation];

                $callableRelations[$relation] = function ($query) use ($load, $request, $relations) {
                    $load($query, $request, $relations);
                };
            } else {
                $nonCallableRelations[] = $relation;
            }
        }

        if (! empty($callableRelations)) {
            $models->loadMissing($callableRelations);
        }

        if (! empty($nonCallableRelations)) {
            $models->loadMissing($nonCallableRelations);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractInclude(ServerRequestInterface $request)
    {
        $available = array_merge($this->include, $this->optionalInclude);

        return $this->buildParameters($request)->getInclude($available) ?: $this->include;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function extractFields(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getFields();
    }

    /**
     * @param ServerRequestInterface $request
     * @return array|null
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractSort(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getSort($this->sortFields) ?: $this->sort;
    }

    /**
     * @param ServerRequestInterface $request
     * @return int
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractOffset(ServerRequestInterface $request)
    {
        return (int) $this->buildParameters($request)->getOffset($this->extractLimit($request)) ?: 0;
    }

    /**
     * @param ServerRequestInterface $request
     * @return int
     */
    protected function extractLimit(ServerRequestInterface $request)
    {
        return (int) $this->buildParameters($request)->getLimit($this->maxLimit) ?: $this->limit;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function extractFilter(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getFilter() ?: [];
    }

    /**
     * @param ServerRequestInterface $request
     * @return Parameters
     */
    protected function buildParameters(ServerRequestInterface $request)
    {
        return new Parameters($request->getQueryParams());
    }

    protected function sortIsDefault(ServerRequestInterface $request): bool
    {
        return ! Arr::get($request->getQueryParams(), 'sort');
    }

    /**
     * 设置用于序列化端点数据的序列化器
     *
     * @param string $serializer
     */
    public function setSerializer(string $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * 默认情况下包含给定的关联关系
     *
     * @param string|array $name
     */
    public function addInclude($name)
    {
        $this->include = array_merge($this->include, (array) $name);
    }

    /**
     * 默认情况下不包含给定的关联关系
     *
     * @param string|array $name
     */
    public function removeInclude($name)
    {
        $this->include = array_diff($this->include, (array) $name);
    }

    /**
     * 使给定的关联关系可用于包含
     *
     * @param string|array $name
     */
    public function addOptionalInclude($name)
    {
        $this->optionalInclude = array_merge($this->optionalInclude, (array) $name);
    }

    /**
     * 不允许包含给定的关联关系
     *
     * @param string|array $name
     */
    public function removeOptionalInclude($name)
    {
        $this->optionalInclude = array_diff($this->optionalInclude, (array) $name);
    }

    /**
     * 设置默认的结果数量
     *
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * 设置结果的最大数量
     *
     * @param int $max
     */
    public function setMaxLimit(int $max)
    {
        $this->maxLimit = $max;
    }

    /**
     * 允许按给定字段对结果进行排序
     *
     * @param string|array $field
     */
    public function addSortField($field)
    {
        $this->sortFields = array_merge($this->sortFields, (array) $field);
    }

    /**
     * 不允许按给定字段对结果进行排序
     *
     * @param string|array $field
     */
    public function removeSortField($field)
    {
        $this->sortFields = array_diff($this->sortFields, (array) $field);
    }

    /**
     * 设置结果的默认排序顺序
     *
     * @param array $sort
     */
    public function setSort(array $sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return Container
     */
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * @param Container $container
     *
     * @internal
     */
    public static function setContainer(Container $container)
    {
        static::$container = $container;
    }

    /**
     * @param string $controllerClass
     * @param callable $callback
     *
     * @internal
     */
    public static function addDataPreparationCallback(string $controllerClass, callable $callback)
    {
        if (! isset(static::$beforeDataCallbacks[$controllerClass])) {
            static::$beforeDataCallbacks[$controllerClass] = [];
        }

        static::$beforeDataCallbacks[$controllerClass][] = $callback;
    }

    /**
     * @param string $controllerClass
     * @param callable $callback
     *
     * @internal
     */
    public static function addSerializationPreparationCallback(string $controllerClass, callable $callback)
    {
        if (! isset(static::$beforeSerializationCallbacks[$controllerClass])) {
            static::$beforeSerializationCallbacks[$controllerClass] = [];
        }

        static::$beforeSerializationCallbacks[$controllerClass][] = $callback;
    }

    /**
     * @internal
     */
    public static function setLoadRelations(string $controllerClass, array $relations)
    {
        if (! isset(static::$loadRelations[$controllerClass])) {
            static::$loadRelations[$controllerClass] = [];
        }

        static::$loadRelations[$controllerClass] = array_merge(static::$loadRelations[$controllerClass], $relations);
    }

    /**
     * @internal
     */
    public static function setLoadRelationCallables(string $controllerClass, array $relations)
    {
        if (! isset(static::$loadRelationCallables[$controllerClass])) {
            static::$loadRelationCallables[$controllerClass] = [];
        }

        static::$loadRelationCallables[$controllerClass] = array_merge(static::$loadRelationCallables[$controllerClass], $relations);
    }
}
