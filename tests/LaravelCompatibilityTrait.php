<?php

namespace Sofa\Eloquence\Tests;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Connection;
use Mockery\MockInterface;

trait LaravelCompatibilityTrait
{
    /**
     * @param MockInterface|Connection $connection
     */
    protected function supportLaravel58($connection)
    {
        if (method_exists(Connection::class, 'query')) {
            $connection->shouldReceive('query')->andReturnUsing(function() use ($connection) {
                return new QueryBuilder(
                    $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
                );
            });
        }
    }
}
