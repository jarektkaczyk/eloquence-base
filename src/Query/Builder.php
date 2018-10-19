<?php

namespace Sofa\Eloquence\Query;

use Sofa\Eloquence\Subquery;
use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Backup some fields for the pagination count.
     *
     * @return void
     */
    protected function backupFieldsForCount()
    {
        foreach (['orders', 'limit', 'offset', 'columns'] as $field) {
            $this->backups[$field] = $this->{$field};

            $this->{$field} = null;
        }

        $bindings = ($this->from instanceof Subquery) ? ['order'] : ['order', 'select'];

        foreach ($bindings as $key) {
            $this->bindingBackups[$key] = $this->bindings[$key];

            $this->bindings[$key] = [];
        }
    }

    /**
     * Restore some fields after the pagination count.
     *
     * @return void
     */
    protected function restoreFieldsForCount()
    {
        foreach ($this->backups as $field => $value) {
            $this->{$field} = $value;
        }

        foreach ($this->bindingBackups as $key => $value) {
            $this->bindings[$key] = $value;
        }

        $this->backups = $this->bindingBackups = [];
    }

    /**
     * Run a pagination count query.
     *
     * @param  array  $columns
     * @return array
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        $bindings = $this->from instanceof Subquery ? ['order'] : ['select', 'order'];

        return $this->cloneWithout(['columns', 'orders', 'limit', 'offset'])
                    ->cloneWithoutBindings($bindings)
                    ->setAggregate('count', $this->withoutSelectAliases($columns))
                    ->get()->all();
    }
}
