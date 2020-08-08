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
}
