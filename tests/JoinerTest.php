<?php

namespace Sofa\Eloquence\Tests;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Mockery;
use Sofa\Eloquence\Relations\JoinerFactory;

class JoinerTest extends TestCase
{
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new JoinerFactory;
    }

    /** @test */
    public function it_joins_dot_nested_relations()
    {
        $sql = 'select * from "users" ' .
               'inner join "profiles" on "users"."profile_id" = "profiles"."id" ' .
               'inner join "companies" on "companies"."morphable_id" = "profiles"."id" and "companies"."morphable_type" = ?';

        $query = $this->getQuery();
        $joiner = $this->factory->make($query);

        $joiner->join('profile.company');

        $this->assertEquals($sql, $query->toSql());
    }

    /** @test */
    public function it_joins_morph_to_many_relations()
    {
        $sql = 'select * from "users" ' .
        'inner join "profiles" on "users"."profile_id" = "profiles"."id" ' .
        'inner join "taggables" on "taggables"."taggable_id" = "profiles"."id" ' .
        'inner join "tags" on "taggables"."joiner_tag_stub_id" = "tags"."id" and "taggables"."taggable_type" = ?';

        $query = $this->getQuery();
        $joiner = $this->factory->make($query);

        $joiner->join('profile.tags');

        $this->assertEquals($sql, $query->toSql());
    }

    /** @test */
    public function it_cant_join_morphTo()
    {
        $this->expectException(LogicException::class);
        $query = $this->getQuery();
        $joiner = $this->factory->make($query);

        $joiner->join('morphs');
    }

    /** @test */
    public function it_joins_relations_on_query_builder()
    {
        $sql = 'select * from "users" ' .
               'right join "company_user" on "company_user"."user_id" = "users"."id" ' .
               'right join "companies" on "company_user"."company_id" = "companies"."id"';

        $eloquent = $this->getQuery();
        $model = $eloquent->getModel();
        $query = $eloquent->getQuery();
        $joiner = $this->factory->make($query, $model);

        $joiner->rightJoin('companies');

        $this->assertEquals($sql, $query->toSql());
    }

    /** @test */
    public function it_joins_relations_on_eloquent_builder()
    {
        $sql = 'select * from "users" ' .
               'left join "companies" on "companies"."user_id" = "users"."id" ' .
               'left join "profiles" on "profiles"."company_id" = "companies"."id"';

        $query = $this->getQuery();
        $joiner = $this->factory->make($query);

        $joiner->leftJoin('profiles');

        $this->assertEquals($sql, $query->toSql());
    }

    /** @test */
    public function it_joins_nested_relations_with_soft_delete()
    {
        $sql = 'select * from "users" ' .
            'inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null ' .
            'inner join "comments" on "comments"."post_id" = "posts"."id"';

        $query = $this->getQuery();
        $joiner = $this->factory->make($query);

        $joiner->join('softDeletingPosts.comments');

        $this->assertEquals($sql, $query->toSql());
    }

    public function getQuery()
    {
        $model = new JoinerUserStub;
        $grammarClass = "Illuminate\Database\Query\Grammars\SQLiteGrammar";
        $processorClass = "Illuminate\Database\Query\Processors\SQLiteProcessor";
        $grammar = new $grammarClass;
        $processor = new $processorClass;
        $schema = Mockery::mock('StdClass');
        $connection = Mockery::mock(Connection::class)->makePartial();
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);
        $resolver = Mockery::mock(ConnectionResolver::class)->makePartial();
        $resolver->shouldReceive('connection')->andReturn($connection);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);

        return $model->newQuery();
    }
}

class JoinerUserStub extends Model
{
    protected $table = 'users';

    public function profile()
    {
        return $this->belongsTo('Sofa\Eloquence\Tests\JoinerProfileStub', 'profile_id');
    }

    public function companies()
    {
        return $this->belongsToMany('Sofa\Eloquence\Tests\JoinerCompanyStub', 'company_user', 'user_id', 'company_id');
    }

    public function profiles()
    {
        // due to lack of getters on HasManyThrough this relation works only with default fk!
        $related = 'Sofa\Eloquence\Tests\JoinerProfileStub';
        $through = 'Sofa\Eloquence\Tests\JoinerCompanyStub';

        return $this->hasManyThrough($related, $through, 'user_id', 'company_id');
    }

    public function posts()
    {
        return $this->hasMany('Sofa\Eloquence\Tests\JoinerPostStub', 'user_id');
    }

    public function softDeletingPosts()
    {
        return $this->hasMany('Sofa\Eloquence\Tests\JoinerSoftDeletingStub', 'user_id');
    }

    public function morphed()
    {
        return $this->morphOne('Sofa\Eloquence\Tests\MorphOneStub');
    }

    public function morphs()
    {
        return $this->morphTo();
    }
}

class JoinerProfileStub extends Model
{
    protected $table = 'profiles';

    public function company()
    {
        return $this->morphOne('Sofa\Eloquence\Tests\JoinerCompanyStub', 'morphable');
    }

    public function tags()
    {
        return $this->morphToMany('Sofa\Eloquence\Tests\JoinerTagStub', 'taggable');
    }
}

class JoinerCompanyStub extends Model
{
    protected $table = 'companies';
}

class JoinerPostStub extends Model
{
    protected $table = 'posts';
}

class JoinerSoftDeletingStub extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany('Sofa\Eloquence\Tests\JoinerCommentStub', 'post_id');
    }
}

class JoinerCommentStub extends Model
{
    protected $table = 'comments';
}

class MorphOneStub extends Model
{
    protected $table = 'morphs';
}

class JoinerTagStub extends Model
{
    protected $table = 'tags';

    public function profiles()
    {
        return $this->morphedByMany('Sofa\Eloquence\Tests\JoinerProfileStub', 'taggable');
    }
}
