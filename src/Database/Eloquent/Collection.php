<?php

namespace Forumkit\Database\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * 这样做是为了防止在使用可见性范围时发生冲突。
     * 如果没有这个，我们在使用可见性范围时会得到以下示例查询。
     *
     * ```sql
     * SELECT `id`, (
     *   SELECT count(*)
     *   FROM `posts` AS `laravel_reserved_0`
     *   INNER JOIN `post_mentions_post` ON `laravel_reserved_0`.`id` = `post_mentions_post`.`post_id`
     *   WHERE `posts`.`id` = `post_mentions_post`.`mentions_post_id`
     *   ---   ^^^^^^^ t 这就是问题所在，可见性范围始终采用默认表名，而不是Laravel 自动生成的别名。
     *
     *     AND `TYPE` in ('discussionTagged', 'discussionStickied', 'discussionLocked', 'comment', 'discussionRenamed')
     * ) AS `mentioned_by_count`
     * FROM `posts`
     * WHERE `posts`.`id` in (23642)
     * ```
     *
     * 因此，通过在父查询上应用别名，我们防止 Laravel 自动对子查询进行别名化。
     */
    public function loadAggregate($relations, $column, $function = null)
    {
        if ($this->isEmpty()) {
            return $this;
        }

        return $this->first()->withTableAlias(function () use ($relations, $column, $function) {
            return parent::loadAggregate($relations, $column, $function);
        });
    }
}
