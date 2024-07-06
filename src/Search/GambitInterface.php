<?php

namespace Forumkit\Search;

interface GambitInterface
{
    /**
     * 对搜索器应用条件，针对搜索字符串的一部分。
     *
     * @param SearchState $search 搜索状态对象
     * @param string $bit 搜索字符串的一部分
     * @return bool 是否针对这一部分启用了gambit（策略）
     */
    public function apply(SearchState $search, $bit);
}
