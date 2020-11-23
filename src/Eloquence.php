<?php

namespace Sofa\Eloquence;

use Illuminate\Database\Connection;
use Sofa\Hookable\Hookable;
use Sofa\Hookable\Contracts\ArgumentBag;
use Sofa\Eloquence\Query\Builder as QueryBuilder;
use Sofa\Eloquence\AttributeCleaner\Observer as AttributeCleaner;
use Sofa\Eloquence\Contracts\CleansAttributes;

/**
 * This trait is an entry point for all the hooks that we want to apply
 * on the Eloquent Model and Builder in order to let the magic happen.
 *
 * It also provides hasColumn and getColumnListing helper methods
 * so you can easily list or check columns in the model's table.
 *
 * @version 5.5
 *
 * @method Connection getConnection()
 * @method string getTable()
 */
trait Eloquence
{
    use Hookable;

    /**
     * Model's table column listing.
     *
     * @var array
     */
    protected static $columnListing = [];

    /**
     * Boot the trait.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function bootEloquence()
    {
        if (is_subclass_of(static::class, CleansAttributes::class)) {
            static::observe(new AttributeCleaner);
        }
    }

    /**
     * Determine whether where should be treated as whereNull.
     *
     * @param  string $method
     * @param  ArgumentBag $args
     * @return boolean
     */
    protected function isWhereNull($method, ArgumentBag $args)
    {
        return $method === 'whereNull' || $method === 'where' && $this->isWhereNullByArgs($args);
    }

    /**
     * Determine whether where is a whereNull by the arguments passed to where method.
     *
     * @param  ArgumentBag $args
     * @return boolean
     */
    protected function isWhereNullByArgs(ArgumentBag $args)
    {
        return is_null($args->get('operator'))
            || is_null($args->get('value')) && !in_array($args->get('operator'), ['<>', '!=']);
    }

    /**
     * Extract real name and alias from the sql select clause.
     *
     * @param  string $column
     * @return array
     */
    protected function extractColumnAlias($column)
    {
        $alias = $column;

        if (strpos($column, ' as ') !== false) {
            list($column, $alias) = explode(' as ', $column);
        }

        return [$column, $alias];
    }

    /**
     * Get the target relation and column from the mapping.
     *
     * @param  string $mapping
     * @return array
     */
    public function parseMappedColumn($mapping)
    {
        $segments = explode('.', $mapping);

        $column = array_pop($segments);

        $target = implode('.', $segments);

        return [$target, $column];
    }

    /**
     * Determine whether the key is meta attribute or actual table field.
     *
     * @param  string  $key
     * @return boolean
     */
    public static function hasColumn($key)
    {
        static::loadColumnListing();

        return in_array((string) $key, static::$columnListing);
    }

    /**
     * Get searchable columns defined on the model.
     *
     * @return array
     */
    public function getSearchableColumns()
    {
        return (property_exists($this, 'searchableColumns')) ? $this->searchableColumns : [];
    }

    /**
     * Get model table columns.
     *
     * @return array
     */
    public static function getColumnListing()
    {
        static::loadColumnListing();

        return static::$columnListing;
    }

    /**
     * Fetch model table columns.
     *
     * @return void
     */
    protected static function loadColumnListing()
    {
        if (empty(static::$columnListing)) {
            $instance = new static;

            static::$columnListing = $instance->getConnection()
                                        ->getSchemaBuilder()
                                        ->getColumnListing($instance->getTable());
        }
    }

    /**
     * Create new Eloquence query builder for the instance.
     *
     * @param  \Sofa\Eloquence\Query\Builder $query
     * @return \Sofa\Eloquence\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Sofa\Eloquence\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }
}
