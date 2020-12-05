<?php

namespace Sofa\Eloquence\Relations;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause as Join;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Sofa\Eloquence\Contracts\Relations\Joiner as JoinerContract;

class Joiner implements JoinerContract
{
    /**
     * Processed query instance.
     *
     * @var Builder
     */
    protected $query;

    /**
     * Parent model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Create new joiner instance.
     *
     * @param Builder $query
     * @param Model $model
     */
    public function __construct(Builder $query, Model $model)
    {
        $this->query = $query;
        $this->model = $model;
    }

    /**
     * Join related tables.
     *
     * @param  string $target
     * @param  string $type
     * @return Model
     */
    public function join($target, $type = 'inner')
    {
        $related = $this->model;

        foreach (explode('.', $target) as $segment) {
            $related = $this->joinSegment($related, $segment, $type);
        }

        return $related;
    }

    /**
     * Left join related tables.
     *
     * @param  string $target
     * @return Model
     */
    public function leftJoin($target)
    {
        return $this->join($target, 'left');
    }

    /**
     * Right join related tables.
     *
     * @param  string $target
     * @return Model
     */
    public function rightJoin($target)
    {
        return $this->join($target, 'right');
    }

    /**
     * Join relation's table accordingly.
     *
     * @param Model $parent
     * @param  string $segment
     * @param  string $type
     * @return Model
     */
    protected function joinSegment(Model $parent, $segment, $type)
    {
        $relation = $parent->{$segment}();
        $related = $relation->getRelated();
        $table = $related->getTable();

        if ($relation instanceof BelongsToMany || $relation instanceof HasManyThrough) {
            $this->joinIntermediate($parent, $relation, $type);
        }

        if (!$this->alreadyJoined($join = $this->getJoinClause($parent, $relation, $table, $type))) {
            $this->query->joins[] = $join;
        }

        return $related;
    }

    /**
     * Determine whether the related table has been already joined.
     *
     * @param Join $join
     * @return bool
     */
    protected function alreadyJoined(Join $join)
    {
        return in_array($join, (array) $this->query->joins);
    }

    /**
     * Get the join clause for related table.
     *
     * @param Model $parent
     * @param Relation $relation
     * @param  string $type
     * @param  string $table
     * @return Join
     */
    protected function getJoinClause(Model $parent, Relation $relation, $table, $type)
    {
        [$fk, $pk] = $this->getJoinKeys($relation);

        $join = (new Join($this->query, $type, $table))->on($fk, '=', $pk);

        /** @var Model|SoftDeletes $related */
        $related = $relation->getRelated();
        if (method_exists($related, 'getQualifiedDeletedAtColumn')) {
            $join->whereNull($related->getQualifiedDeletedAtColumn());
        }

        if ($relation instanceof MorphOneOrMany) {
            $join->where($relation->getQualifiedMorphType(), '=', $parent->getMorphClass());
        } elseif ($relation instanceof MorphToMany || $relation instanceof MorphMany) {
            $join->where($relation->getMorphType(), '=', $parent->getMorphClass());
        }

        return $join;
    }

    /**
     * Join pivot or 'through' table.
     *
     * @param Model $parent
     * @param Relation $relation
     * @param  string $type
     * @return void
     */
    protected function joinIntermediate(Model $parent, Relation $relation, $type)
    {
        if ($relation instanceof BelongsToMany) {
            $table = $relation->getTable();
            $fk = $relation->getQualifiedForeignPivotKeyName();
        } else {
            $table = $relation->getParent()->getTable();
            $fk = $relation->getQualifiedFirstKeyName();
        }

        $pk = $parent->getQualifiedKeyName();

        if (!$this->alreadyJoined($join = (new Join($this->query, $type, $table))->on($fk, '=', $pk))) {
            $this->query->joins[] = $join;
        }
    }

    /**
     * Get pair of the keys from relation in order to join the table.
     *
     * @param Relation $relation
     * @return array
     *
     * @throws LogicException
     */
    protected function getJoinKeys(Relation $relation)
    {
        if ($relation instanceof MorphTo) {
            throw new LogicException('MorphTo relation cannot be joined.');
        }

        if ($relation instanceof HasOneOrMany) {
            return [$relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName()];
        }

        if ($relation instanceof BelongsTo) {
            return [$relation->getQualifiedForeignKeyName(), $relation->getQualifiedOwnerKeyName()];
        }

        if ($relation instanceof BelongsToMany) {
            return [$relation->getQualifiedRelatedPivotKeyName(), $relation->getRelated()->getQualifiedKeyName()];
        }

        if ($relation instanceof HasManyThrough) {
            $fk = $relation->getQualifiedFarKeyName();

            return [$fk, $relation->getQualifiedParentKeyName()];
        }
    }
}
