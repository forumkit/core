<?php

namespace Forumkit\Frontend\Compiler;

use axy\sourcemap\SourceMap;
use Forumkit\Frontend\Compiler\Source\FileSource;

/**
 * @internal
 */
class JsCompiler extends RevisionCompiler
{
    protected function save(string $file, array $sources): bool
    {
        if (empty($sources)) {
            return false;
        }

        $mapFile = $file.'.map';

        $map = new SourceMap();
        $map->file = $mapFile;
        $output = [];
        $line = 0;

        //  对于每个源，获取其内容并将其添加到输出中。
        // 对于文件源，如果存在源代码映射，则将其添加到输出的源代码映射中。
        foreach ($sources as $source) {
            $content = $source->getContent();

            if ($source instanceof FileSource) {
                $sourceMap = $source->getPath().'.map';

                if (file_exists($sourceMap)) {
                    $map->concat($sourceMap, $line);
                }
            }

            $content = $this->format($content);
            $output[] = $content;
            $line += substr_count($content, "\n") + 1;
        }

        // 在文件末尾添加一个注释，指向我们刚刚构建的源代码映射。然后，我们将 JS 文件和映射存储在资产目录中。
        $output[] = '//# sourceMappingURL='.$this->assetsDir->url($mapFile);

        $this->assetsDir->put($file, implode("\n", $output));
        $this->assetsDir->put($mapFile, json_encode($map, JSON_UNESCAPED_SLASHES));

        return true;
    }

    protected function format(string $string): string
    {
        return preg_replace('~//# sourceMappingURL.*$~m', '', $string)."\n";
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(string $file)
    {
        parent::delete($file);

        if ($this->assetsDir->exists($mapFile = $file.'.map')) {
            $this->assetsDir->delete($mapFile);
        }
    }
}
