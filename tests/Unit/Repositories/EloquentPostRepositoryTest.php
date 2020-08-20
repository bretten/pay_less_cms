<?php


namespace Tests\Unit\Repositories;


use App\Contracts\Models\Post as PostContract;
use App\Post;
use App\Repositories\EloquentPostRepository;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Mock\EloquentMocker;

class EloquentPostRepositoryTest extends TestCase
{
    /**
     * Tests that the repository can get all Post rows
     *
     * @return void
     */
    public function testGetAll()
    {
        // Setup
        $expectedPost1 = new PostContract(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $expectedPost2 = new PostContract(2, 'site1', 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $expectedPosts = [
            $expectedPost1, $expectedPost2
        ];
        $eloquentPost1 = EloquentMocker::mockPost(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $eloquentPost2 = EloquentMocker::mockPost(2, 'site1', 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $eloquentPosts = [
            $eloquentPost1, $eloquentPost2
        ];
        $builder = Mockery::mock(Builder::class, function ($mock) use ($eloquentPosts) {
            $mock->shouldReceive('withTrashed')
                ->andReturn($mock);
            $mock->shouldReceive('get')
                ->andReturn($eloquentPosts);
        });
        $post = Mockery::mock(Post::class, function ($mock) use ($builder) {
            $mock->shouldReceive('newQuery')
                ->andReturn($builder);
        });
        $repo = new EloquentPostRepository($post);

        // Execute
        $result = $repo->getAll();

        // Assert
        $this->assertEquals($expectedPosts, $result);
    }

    /**
     * Tests that the repository can get a Post by ID
     *
     * @return void
     */
    public function testGetById()
    {
        // Setup
        $expectedPost = new PostContract(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $eloquentPost = EloquentMocker::mockPost(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null,);
        $post = Mockery::mock(Post::class, function ($mock) use ($eloquentPost) {
            $mock->shouldReceive('findOrFail')
                ->with(1)
                ->andReturn($eloquentPost);
        });
        $repo = new EloquentPostRepository($post);

        // Execute
        $result = $repo->getById(1);

        // Assert
        $this->assertEquals($expectedPost, $result);
    }

    /**
     * Tests that the repository throws a Not Found exception when trying to
     * retrieve a Post by an invalid ID
     *
     * @return void
     */
    public function testGetByIdThrowsNotFoundExceptionWhenPassedInvalidId()
    {
        $this->markTestIncomplete("Test not useful until data source can be mocked");

        // Setup
        $post = Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('findOrFail')
                ->with(7)
                ->andThrow(new ModelNotFoundException);
        });
        $repo = new EloquentPostRepository($post);

        // Execute and Assert
        $this->expectException(ModelNotFoundException::class);
        $repo->getById(7);
    }

    /**
     * Tests that the repository can create a Post
     *
     * @return void
     */
    public function testCreate()
    {
        // Setup
        $expected = true;
        $post = Mockery::mock(Post::class, function ($mock) use ($expected) {
            $mock->shouldReceive('setAttribute')
                ->with('site', 'site1')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('title', 'title1')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('content', 'content1')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('human_readable_url', 'human-readable-url1')
                ->times(1);
            $mock->shouldReceive('save')
                ->andReturn($expected);
        });
        $repo = new EloquentPostRepository($post);

        // Execute
        $result = $repo->create('site1', 'title1', 'content1', 'human-readable-url1');

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the repository can edit a Post
     *
     * @return void
     */
    public function testEdit()
    {
        // Setup
        $id = 1;
        $expected = true;
        $post = Mockery::mock(Post::class, function ($mock) use ($id, $expected) {
            $mock->shouldReceive('find')
                ->with($id)
                ->times(1)
                ->andReturn($mock);
            $mock->shouldReceive('setAttribute')
                ->with('site', 'site1 v2')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('title', 'title1 v2')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('content', 'content1 v2')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('human_readable_url', 'human-readable-url1-v2')
                ->times(1);
            $mock->shouldReceive('save')
                ->andReturn($expected);
        });
        $repo = new EloquentPostRepository($post);

        // Execute
        $result = $repo->update($id, 'site1 v2', 'title1 v2', 'content1 v2', 'human-readable-url1-v2');

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the repository can delete a Post by ID
     *
     * @return void
     * @throws \Exception
     */
    public function testDelete()
    {
        // Setup
        $id = 1;
        $post = Mockery::mock(Post::class, function ($mock) use ($id) {
            $mock->shouldReceive('find')
                ->with($id)
                ->times(1)
                ->andReturn($mock);
            $mock->shouldReceive('delete')
                ->times(1)
                ->andReturn(true);
        });
        $repo = new EloquentPostRepository($post);

        // Execute
        $result = $repo->delete($id);

        // Assert
        $this->assertTrue($result);
    }
}
