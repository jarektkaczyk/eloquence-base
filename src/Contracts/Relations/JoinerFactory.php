<?php

namespace Sofa\Eloquence\Contracts\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

interface JoinerFactory
{
    /**
     * Create new joiner instance.
     *
     * @param  Builder $query
     * @return Joiner
     */
    public static function make($query, Model $model = null);
}
