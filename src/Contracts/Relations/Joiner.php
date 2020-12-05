<?php

namespace Sofa\Eloquence\Contracts\Relations;

use Illuminate\Database\Eloquent\Model;

interface Joiner
{
    /**
     * Join tables of the provided relations and return related model.
     *
     * @param  string $relations
     * @param  string $type
     * @return Model
     */
    public function join($relations, $type = 'inner');
}
