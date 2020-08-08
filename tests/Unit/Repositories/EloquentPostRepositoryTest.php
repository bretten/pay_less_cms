<?php


namespace Repositories;


use App\Post;
use App\Repositories\EloquentPostRepository;
use Mockery;
use PHPUnit\Framework\TestCase;

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
        $expected = ['Post1', 'Post2'];
        $post = Mockery::mock(Post::class, function ($mock) use ($expected) {
            $mock->shouldReceive('all')
                ->andReturn($expected);
        });
        $repo = new EloquentPostRepository($post);

        // Execute
        $result = $repo->getAll();

        // Assert
        $this->assertEquals($expected, $result);
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
        $result = $repo->create("title1", "content1", "human-readable-url1");

        // Assert
        $this->assertEquals($expected, $result);
    }
}
