<?php

namespace Forumkit\Discussion\Search\Gambit;

use Forumkit\Discussion\Discussion;
use Forumkit\Post\Post;
use Forumkit\Search\GambitInterface;
use Forumkit\Search\SearchState;
use Illuminate\Database\Query\Expression;

class FulltextGambit implements GambitInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $bit)
    {
        // 用空格替换所有非单词字符。
        // 我们这样做是为了防止 MySQL 全文搜索布尔模式产生作用：https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
        $bit = preg_replace('/[^\p{L}\p{N}\p{M}_]+/u', ' ', $bit);

        $query = $search->getQuery();
        $grammar = $query->getGrammar();

        $discussionSubquery = Discussion::select('id')
            ->selectRaw('NULL as score')
            ->selectRaw('first_post_id as most_relevant_post_id')
            ->whereRaw('MATCH('.$grammar->wrap('discussions.title').') AGAINST (? IN BOOLEAN MODE)', [$bit]);

        // 构造一个子查询来检索包含相关帖子的讨论。
        // 检索每个讨论的帖子的总体相关性，稍后在 order by 子句中使用，并检索最相关帖子的 ID。
        $subquery = Post::whereVisibleTo($search->getActor())
            ->select('posts.discussion_id')
            ->selectRaw('SUM(MATCH('.$grammar->wrap('posts.content').') AGAINST (?)) as score', [$bit])
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT('.$grammar->wrap('posts.id').' ORDER BY MATCH('.$grammar->wrap('posts.content').') AGAINST (?) DESC, '.$grammar->wrap('posts.number').'), \',\', 1) as most_relevant_post_id', [$bit])
            ->where('posts.type', 'comment')
            ->whereRaw('MATCH('.$grammar->wrap('posts.content').') AGAINST (? IN BOOLEAN MODE)', [$bit])
            ->groupBy('posts.discussion_id')
            ->union($discussionSubquery);

        // 将子查询加入主搜索查询中，并将结果限定为具有相关标题或包含相关帖子的讨论。
        $query
            ->addSelect('posts_ft.most_relevant_post_id')
            ->join(
                new Expression('('.$subquery->toSql().') '.$grammar->wrapTable('posts_ft')),
                'posts_ft.discussion_id',
                '=',
                'discussions.id'
            )
            ->groupBy('discussions.id')
            ->addBinding($subquery->getBindings(), 'join');

        $search->setDefaultSort(function ($query) use ($grammar, $bit) {
            $query->orderByRaw('MATCH('.$grammar->wrap('discussions.title').') AGAINST (?) desc', [$bit]);
            $query->orderBy('posts_ft.score', 'desc');
        });

        return true;
    }
}
