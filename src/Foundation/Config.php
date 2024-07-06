<?php

namespace Forumkit\Foundation;

use ArrayAccess;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class Config implements ArrayAccess
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->requireKeys('url');
    }

    public function url(): UriInterface
    {
        return new Uri(rtrim($this->data['url'], '/'));
    }

    public function inDebugMode(): bool
    {
        return $this->data['debug'] ?? false;
    }

    public function inMaintenanceMode(): bool
    {
        return $this->data['offline'] ?? false;
    }

    private function requireKeys(...$keys)
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->data)) {
                throw new InvalidArgumentException(
                    "配置无效，因为缺少 $key 键"
                );
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return Arr::get($this->data, $offset);
    }

    public function offsetExists($offset): bool
    {
        return Arr::has($this->data, $offset);
    }

    public function offsetSet($offset, $value): void
    {
        throw new RuntimeException('配置是不可变的');
    }

    public function offsetUnset($offset): void
    {
        throw new RuntimeException('配置是不可变的');
    }
}
