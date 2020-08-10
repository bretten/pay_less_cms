<?php


namespace Services;


use App\Services\FilesystemPostPublisher;
use League\Flysystem\FilesystemInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class FilesystemPostPublisherTest extends TestCase
{
    /**
     * Tests that Posts can be published to the Filesystem
     *
     * @return void
     */
    public function testPublishPostsToFilesystem()
    {
        // Setup
        $post1 = new \stdClass();
        $post1->content = 'content1';
        $post1->human_readable_url = 'url1';
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $posts = [
            $post1, $post2
        ];
        $post = Mockery::mock(FilesystemInterface::class, function ($mock) {
            $mock->shouldReceive('put')
                ->with('url1', 'content1')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'content2')
                ->times(1)
                ->andReturn(true);
        });
        $publisher = new FilesystemPostPublisher($post);

        // Execute
        $result = $publisher->publish($posts);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that Posts can be published to the Filesystem with an error
     *
     * @return void
     */
    public function testPublishPostsToFilesystemWithFailure()
    {
        // Setup
        $post1 = new \stdClass();
        $post1->content = 'content1';
        $post1->human_readable_url = 'url1';
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $posts = [
            $post1, $post2
        ];
        $post = Mockery::mock(FilesystemInterface::class, function ($mock) {
            $mock->shouldReceive('put')
                ->with('url1', 'content1')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'content2')
                ->times(1)
                ->andReturn(false); // Failed
        });
        $publisher = new FilesystemPostPublisher($post);

        // Execute
        $result = $publisher->publish($posts);

        // Assert
        $this->assertFalse($result);
    }
}
