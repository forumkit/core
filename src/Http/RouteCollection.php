<?php

namespace Forumkit\Http;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class RouteCollection
{
    /**
     * @var array
     */
    protected $reverse = [];

    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $pendingRoutes = [];

    public function __construct()
    {
        $this->dataGenerator = new DataGenerator\GroupCountBased;
        $this->routeParser = new RouteParser\Std;
    }

    public function get($path, $name, $handler)
    {
        return $this->addRoute('GET', $path, $name, $handler);
    }

    public function post($path, $name, $handler)
    {
        return $this->addRoute('POST', $path, $name, $handler);
    }

    public function put($path, $name, $handler)
    {
        return $this->addRoute('PUT', $path, $name, $handler);
    }

    public function patch($path, $name, $handler)
    {
        return $this->addRoute('PATCH', $path, $name, $handler);
    }

    public function delete($path, $name, $handler)
    {
        return $this->addRoute('DELETE', $path, $name, $handler);
    }

    public function addRoute($method, $path, $name, $handler)
    {
        if (isset($this->routes[$name])) {
            throw new \RuntimeException("路由 $name 已经存在");
        }

        $this->routes[$name] = $this->pendingRoutes[$name] = compact('method', 'path', 'handler');

        return $this;
    }

    public function removeRoute(string $name): self
    {
        unset($this->routes[$name], $this->pendingRoutes[$name]);

        return $this;
    }

    protected function applyRoutes(): void
    {
        foreach ($this->pendingRoutes as $name => $route) {
            $routeDatas = $this->routeParser->parse($route['path']);

            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($route['method'], $routeData, ['name' => $name, 'handler' => $route['handler']]);
            }

            $this->reverse[$name] = $routeDatas;
        }

        $this->pendingRoutes = [];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouteData()
    {
        if (! empty($this->pendingRoutes)) {
            $this->applyRoutes();
        }

        return $this->dataGenerator->getData();
    }

    protected function fixPathPart($part, array $parameters, string $routeName)
    {
        if (! is_array($part)) {
            return $part;
        }

        if (! array_key_exists($part[0], $parameters)) {
            throw new \InvalidArgumentException("无法为路由 '$routeName'生成URL：必需的部分 '$part[0]' 没有提供值。");
        }

        return $parameters[$part[0]];
    }

    public function getPath($name, array $parameters = [])
    {
        if (! empty($this->pendingRoutes)) {
            $this->applyRoutes();
        }

        if (isset($this->reverse[$name])) {
            $maxMatches = 0;
            $matchingParts = $this->reverse[$name][0];

            // 对于给定的路由名称，我们想要选择最能匹配给定参数的选项。
            // 每个路由选项都是一个部分的数组。每个部分要么是一个常量字符串
            // （这里我们不关心它），要么是一个数组，其中第一个元素是参数名称
            // 第二个元素是一个正则表达式，如果参数匹配，则参数值会被插入到这个正则表达式中。
            foreach ($this->reverse[$name] as $parts) {
                foreach ($parts as $i => $part) {
                    if (is_array($part) && Arr::exists($parameters, $part[0]) && $i > $maxMatches) {
                        $maxMatches = $i;
                        $matchingParts = $parts;
                    }
                }
            }

            $fixedParts = array_map(function ($part) use ($parameters, $name) {
                return $this->fixPathPart($part, $parameters, $name);
            }, $matchingParts);

            return '/'.ltrim(implode('', $fixedParts), '/');
        }

        throw new \RuntimeException("找不到名为 $name 的路由");
    }
}
