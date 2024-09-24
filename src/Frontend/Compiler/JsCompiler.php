<?php

namespace Forumkit\Frontend\Compiler;

use axy\sourcemap\SourceMap;
use Forumkit\Frontend\Compiler\Source\FileSource;

/**
 * JavaScript 编译器类，继承自 RevisionCompiler 类，用于编译和生成 JavaScript 文件及其对应的 SourceMap 。
 * 
 * @internal
 */
class JsCompiler extends RevisionCompiler
{
    /**
     * 保存编译后的 JavaScript 文件及其 SourceMap 。
     *
     * @param string $file 目标文件的名称（不包括路径）
     * @param array $sources 源文件列表，包含内容或 FileSource 对象
     * @return bool 操作是否成功
     */
    protected function save(string $file, array $sources): bool
    {
        if (empty($sources)) {
            return false;
        }

        $mapFile = $file.'.map'; // SourceMap 文件的名称

        $map = new SourceMap(); // 创建 SourceMap 对象
        $map->file = $mapFile;  // 设置 SourceMap 文件名
        $output = [];           // 存储编译后的内容
        $line = 0;              // 当前行数，用于构建 SourceMap

        // 对于每个源，获取其内容并将其添加到输出中。
        // 对于文件源，如果存在 sourcemap，请将其添加到输出 sourcemap 。
        foreach ($sources as $source) {
            $content = $source->getContent();   // 获取源文件内容

            // 如果源文件是 FileSource 实例，则检查是否存在对应的 SourceMap
            if ($source instanceof FileSource) {
                $sourceMap = $source->getPath().'.map';

                if (file_exists($sourceMap)) {
                    $map->concat($sourceMap, $line);    // 将存在的 SourceMap 合并到当前 SourceMap 中
                }
            }

            $content = $this->format($content); // 格式化内容
            $output[] = $content;               // 将格式化后的内容添加到输出中
            $line += substr_count($content, "\n") + 1;  // 更新行数
        }

        // 在文件末尾添加注释以指向我们刚刚构建的 sourcemap。
        // 然后，我们将 JS 文件和地图存储在我们的 asset 目录中。
        $output[] = '//# sourceMappingURL='.$this->assetsDir->url($mapFile);

        // 将编译后的内容写入目标文件
        $this->assetsDir->put($file, implode("\n", $output));
        // 将 SourceMap 对象序列化为 JSON 并写入文件
        $this->assetsDir->put($mapFile, json_encode($map, JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * 格式化字符串，移除已存在的 SourceMap 注释。
     *
     * @param string $string 输入的字符串
     * @return string 格式化后的字符串
     */
    protected function format(string $string): string
    {
        return preg_replace('~//# sourceMappingURL.*$~m', '', $string)."\n";
    }

    /**
     * 删除指定的文件及其对应的 SourceMap 文件（如果存在）。
     * 
     * {@inheritdoc}
     */
    protected function delete(string $file)
    {
        parent::delete($file);  // 调用父类的delete方法删除文件

        $mapFile = $file.'.map'; // 构造SourceMap文件名

        if ($this->assetsDir->exists($mapFile)) {
            $this->assetsDir->delete($mapFile); // 如果存在，则删除SourceMap文件
        }
    }
}
