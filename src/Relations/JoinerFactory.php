<?php

namespace Sofa\Eloquence\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Sofa\Eloquence\Contracts\Relations\JoinerFactory as FactoryContract;

class JoinerFactory implements FactoryContract
{
    /**
     * Create new joiner instance.
     *
     * @param EloquentBuilder|Builder $query
     * @param Model $model
     * @return Joiner
     */
    public static function make($query, Model $model = null)
    {
        if ($query instanceof EloquentBuilder) {
            $model = $query->getModel();
            $query = $query->getQuery();
        }

        return new Joiner($query, $model);
    }
}
