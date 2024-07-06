<?php

namespace Forumkit\Frontend\Compiler;

use Forumkit\Frontend\Compiler\Source\SourceCollector;
use Forumkit\Frontend\Compiler\Source\SourceInterface;
use Illuminate\Contracts\Filesystem\Cloud;

/**
 * @internal
 */
class RevisionCompiler implements CompilerInterface
{
    const EMPTY_REVISION = 'empty';

    /**
     * @var Cloud
     */
    protected $assetsDir;

    /**
     * @var VersionerInterface
     */
    protected $versioner;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var callable[]
     */
    protected $sourcesCallbacks = [];

    /**
     * @param Cloud $assetsDir
     * @param string $filename
     * @param VersionerInterface|null $versioner 版本控制器（已弃用：在v2.0版本中将移除可为null的设定）
     */
    public function __construct(Cloud $assetsDir, string $filename, VersionerInterface $versioner = null)
    {
        $this->assetsDir = $assetsDir;
        $this->filename = $filename;
        $this->versioner = $versioner ?: new FileVersioner($assetsDir);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    public function commit(bool $force = false)
    {
        $sources = $this->getSources();

        $oldRevision = $this->versioner->getRevision($this->filename);

        $newRevision = $this->calculateRevision($sources);

        // 如果新旧版本号不匹配，或者文件尚未写入磁盘，则保存文件到磁盘
        if ($force || $oldRevision !== $newRevision || ! $this->assetsDir->exists($this->filename)) {
            if (! $this->save($this->filename, $sources)) {
                // 如果文件未写入（因为资源为空），则将版本号设置为特殊值，表示此文件没有 URL
                $newRevision = static::EMPTY_REVISION;
            }

            $this->versioner->putRevision($this->filename, $newRevision);
        }
    }

    public function addSources(callable $callback)
    {
        $this->sourcesCallbacks[] = $callback;
    }

    /**
     * @return SourceInterface[]
     */
    protected function getSources(): array
    {
        $sources = new SourceCollector;

        foreach ($this->sourcesCallbacks as $callback) {
            $callback($sources);
        }

        return $sources->getSources();
    }

    public function getUrl(): ?string
    {
        $revision = $this->versioner->getRevision($this->filename);

        if (! $revision) {
            $this->commit();

            $revision = $this->versioner->getRevision($this->filename);

            if (! $revision) {
                return null;
            }
        }

        if ($revision === static::EMPTY_REVISION) {
            return null;
        }

        $url = $this->assetsDir->url($this->filename);

        // 将版本号作为 GET 参数添加到 URL 中，以表示文件已更改，需要刷新。
        // 返回添加了版本号的 URL。
        return "$url?v=$revision";
    }

    /**
     * @param string $file
     * @param SourceInterface[] $sources
     * @return bool 如果文件已写入，则返回 true；如果没有内容需要写入，则返回 false
     */
    protected function save(string $file, array $sources): bool
    {
        if ($content = $this->compile($sources)) {
            $this->assetsDir->put($file, $content);

            return true;
        }

        return false;
    }

    /**
     * @param SourceInterface[] $sources
     */
    protected function compile(array $sources): string
    {
        $output = '';

        foreach ($sources as $source) {
            $output .= $this->format($source->getContent());
        }

        return $output;
    }

    protected function format(string $string): string
    {
        return $string;
    }

    /**
     * @param SourceInterface[] $sources
     */
    protected function calculateRevision(array $sources): string
    {
        $cacheDifferentiator = [$this->getCacheDifferentiator()];

        foreach ($sources as $source) {
            $cacheDifferentiator[] = $source->getCacheDifferentiator();
        }

        return hash('crc32b', serialize($cacheDifferentiator));
    }

    protected function getCacheDifferentiator(): ?array
    {
        return null;
    }

    public function flush()
    {
        if ($this->versioner->getRevision($this->filename) !== null) {
            $this->delete($this->filename);

            $this->versioner->putRevision($this->filename, null);
        }
    }

    protected function delete(string $file)
    {
        if ($this->assetsDir->exists($file)) {
            $this->assetsDir->delete($file);
        }
    }
}
