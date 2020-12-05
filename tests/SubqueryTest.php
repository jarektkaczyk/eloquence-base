<?php

namespace Sofa\Eloquence\Tests;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Sofa\Eloquence\Subquery;

class SubqueryTest extends TestCase
{
    /** @test */
    public function it_forwards_calls_to_the_builder()
    {
        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())->method('where')->with('foo', 'bar')->willReturnSelf();

        $sub = new Subquery($builder);
        $sub->from = 'table';
        $sub->where('foo', 'bar');

        $this->assertFalse(property_exists($sub, 'from'));
        $this->assertEquals('table', $sub->getQuery()->from);
        $this->assertEquals('table', $sub->from);
    }

    /** @test */
    public function it_prints_as_aliased_query_in_parentheses()
    {
        $grammar = new SQLiteGrammar();
        $builder = $this->createMock(Builder::class);
        $builder->method('getGrammar')->willReturn($grammar);
        $builder->method('toSql')->willReturn('select * from "table" where id = ?');
        $sub = new Subquery($builder);

        $this->assertEquals('(select * from "table" where id = ?)', (string) $sub);

        $sub->setAlias('table_alias');

        $this->assertEquals('(select * from "table" where id = ?) as "table_alias"', (string) $sub);
        $this->assertEquals('table_alias', $sub->getAlias());
    }

    /** @test */
    public function it_accepts_eloquent_and_query_builder()
    {
        $builder = $this->createMock(Builder::class);
        new Subquery($builder);

        $eloquent = $this->createMock(EloquentBuilder::class);
        $eloquent->expects($this->once())->method('getQuery')->willReturn($builder);
        new Subquery($eloquent);
    }
}
