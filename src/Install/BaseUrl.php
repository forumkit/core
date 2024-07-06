<?php

namespace Forumkit\Install;

use Psr\Http\Message\UriInterface;

final class BaseUrl
{
    /** @var string */
    private $normalized;

    private function __construct(string $baseUrl)
    {
        $this->normalized = $this->normalize($baseUrl);
    }

    public static function fromString(string $baseUrl): self
    {
        return new self($baseUrl);
    }

    public static function fromUri(UriInterface $baseUrl): self
    {
        return new self((string) $baseUrl);
    }

    public function __toString(): string
    {
        return $this->normalized;
    }

    /**
     * 为此基本URL的域名生成一个有效的电子邮件地址。
     *
     * 使用给定的邮箱名和已经归一化的主机名来构造电子邮件地址。
     *
     * @param string $mailbox
     * @return string
     */
    public function toEmail(string $mailbox): string
    {
        $host = preg_replace('/^www\./i', '', parse_url($this->normalized, PHP_URL_HOST));

        return "$mailbox@$host";
    }

    private function normalize(string $baseUrl): string
    {
        // 空的基本URL仍然是有效的
        if (empty($baseUrl)) {
            return '';
        }

        $normalizedBaseUrl = trim($baseUrl, '/');
        if (! preg_match('#^https?://#i', $normalizedBaseUrl)) {
            $normalizedBaseUrl = sprintf('http://%s', $normalizedBaseUrl);
        }

        $parseUrl = parse_url($normalizedBaseUrl);

        $path = $parseUrl['path'] ?? null;
        if (isset($parseUrl['path']) && strrpos($parseUrl['path'], '.php') !== false) {
            $path = substr($parseUrl['path'], 0, strrpos($parseUrl['path'], '/'));
        }

        $port = isset($parseUrl['port']) ? ':'.$parseUrl['port'] : '';

        return rtrim(
            sprintf('%s://%s%s%s', $parseUrl['scheme'], $parseUrl['host'], $port, $path),
            '/'
        );
    }
}
