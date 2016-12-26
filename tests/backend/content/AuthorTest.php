<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthorTest extends TestCase {

    protected $author;

    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        DB::beginTransaction();

        $this->author = Mockery::mock('App\Droit\Author\Repo\AuthorInterface');
        $this->app->instance('App\Droit\Author\Repo\AuthorInterface', $this->author);

        $user = factory(App\Droit\User\Entities\User::class,'admin')->create();
        $user->roles()->attach(1);
        $this->actingAs($user);

    }

    public function tearDown()
    {
        Mockery::close();
        DB::rollBack();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testAuthorShow()
    {
        $author = factory(App\Droit\Author\Entities\Author::class)->make(['id' => 1]);

        $this->author->shouldReceive('find')->once()->andReturn($author);

        $this->visit('admin/author/1');
        $this->assertViewHas('author');
    }

    /**
     * @return void
     */
    public function testAuthorStore()
    {
        $author = factory(App\Droit\Author\Entities\Author::class)->make();

        $this->author->shouldReceive('create')->once()->andReturn($author);

        $response = $this->call('POST', 'admin/author',['first_name' => 'Jane', 'last_name' => 'Doe', 'occupation' => 'Test', 'bio' => 'Test', 'photo' => 'jane.jpg', 'rang' => 1]);

        $this->assertRedirectedTo('admin/author');
    }

    /**
     * @return void
     */
    public function testAuthorDelete()
    {
        $this->author->shouldReceive('delete')->once();

        $response = $this->call('DELETE','admin/author/1', [] ,['id' => '1']);

        $this->assertRedirectedTo('admin/author');
    }
}
