<?php

namespace Sofa\Eloquence;

use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @mixin QueryBuilder
 */
class Subquery extends Expression
{
    /**
     * Query builder instance.
     *
     * @var QueryBuilder
     */
    protected $query;

    /**
     * Alias for the subquery.
     *
     * @var string
     */
    protected $alias;

    /**
     * Create new subquery instance.
     *
     * @param QueryBuilder|EloquentBuilder
     * @param string $alias
     */
    public function __construct($query, $alias = null)
    {
        if ($query instanceof EloquentBuilder) {
            $query = $query->getQuery();
        }

        $this->setQuery($query);

        $this->alias = $alias;
    }

    /**
     * Set underlying query builder.
     *
     * @param QueryBuilder $query
     */
    public function setQuery(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Get underlying query builder.
     *
     * @return QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Evaluate query as string.
     *
     * @return string
     */
    public function getValue(Grammar $grammar)
    {
        $sql = '(' . $this->query->toSql() . ')';

        if ($this->alias) {
            $alias = $this->query->getGrammar()->wrapTable($this->alias);

            $sql .= ' as ' . $alias;
        }

        return $sql;
    }

    /**
     * Get subquery alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set subquery alias.
     *
     * @param  string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Pass property calls to the underlying builder.
     *
     * @param  string $property
     * @param  mixed  $value
     * @return mixed
     */
    public function __set($property, $value)
    {
        return $this->query->{$property} = $value;
    }

    /**
     * Pass property calls to the underlying builder.
     *
     * @param  string $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->query->{$property};
    }

    /**
     * Pass method calls to the underlying builder.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        return call_user_func_array([$this->query, $method], $params);
    }
}
