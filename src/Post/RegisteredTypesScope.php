<?php

namespace Forumkit\Post;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class RegisteredTypesScope implements Scope
{
    /**
     * 将范围应用于给定的 Eloquent 查询生成器。
     *
     * @param Builder $builder
     * @param Model $post
     */
    public function apply(Builder $builder, Model $post)
    {
        $query = $builder->getQuery();
        $types = array_keys($post::getModels());
        $query->whereIn('type', $types);
    }
}
