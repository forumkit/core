<?php

namespace Forumkit\Search;

abstract class AbstractRegexGambit implements GambitInterface
{
    /**
     * 获取与部分搜索字符串匹配的正则表达式模式。
     */
    abstract protected function getGambitPattern();

    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $bit)
    {
        if ($matches = $this->match($bit)) {
            list($negate) = array_splice($matches, 1, 1);

            $this->conditions($search, $matches, (bool) $negate);
        }

        return (bool) $matches;
    }

    /**
     * 将部分搜索字符串与此 gambit 进行匹配。
     *
     * @param string $bit
     * @return array|null
     */
    protected function match($bit)
    {
        if (! empty($bit) && preg_match('/^(-?)'.$this->getGambitPattern().'$/i', $bit, $matches)) {
            return $matches;
        }

        return null;
    }

    /**
     * 当 gambit 匹配时，将条件应用到搜索上。
     *
     * @param SearchState $search 搜索对象
     * @param array $matches 从搜索部分中获取的匹配项数组
     * @param bool $negate 部分搜索字符串是否被否定，从而确定条件是否应该被否定
     * @return mixed 任意类型的返回值
     */
    abstract protected function conditions(SearchState $search, array $matches, $negate);
}
