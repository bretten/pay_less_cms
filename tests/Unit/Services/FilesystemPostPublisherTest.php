<?php


namespace Tests\Unit\Services;


use App\Services\FilesystemPostPublisher;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use League\CommonMark\MarkdownConverterInterface;
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
        $post1->deleted_at = false;
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $post2->deleted_at = false;
        $post3 = new \stdClass();
        $post3->content = 'content3';
        $post3->human_readable_url = 'url3';
        $post3->deleted_at = true;
        $posts = [
            $post1, $post2, $post3
        ];

        $markdownConverter = Mockery::mock(MarkdownConverterInterface::class, function ($mock) use ($post1, $post2, $post3) {
            $mock->shouldReceive('convertToHtml')
                ->with($post1->content)
                ->times(1)
                ->andReturn('content1 with markup');
            $mock->shouldReceive('convertToHtml')
                ->with($post2->content)
                ->times(1)
                ->andReturn('content2 with markup');
            $mock->shouldReceive('convertToHtml')
                ->with($post3->content)
                ->times(0);
        });
        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($post1, $post2, $post3) {
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post1])
                ->times(1)
                ->andReturn('content1 with markup rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post2])
                ->times(1)
                ->andReturn('content2 with markup rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with('posts.published.list', ['posts' => [$post1, $post2]])
                ->times(1)
                ->andReturn('posts list rendered in view');
        });
        $filesystem = Mockery::mock(FilesystemInterface::class, function ($mock) {
            $mock->shouldReceive('put')
                ->with('url1', 'content1 with markup rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'content2 with markup rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url3', 'content3 with markup rendered in view')
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('index.html', 'posts list rendered in view')
                ->times(1)
                ->andReturn(true);
        });
        $publisher = new FilesystemPostPublisher($markdownConverter, $viewFactory, $filesystem);

        // Execute
        $result = $publisher->publish($posts);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that Posts can be published to the Filesystem with a custom site view
     *
     * @return void
     */
    public function testPublishPostsWithCustomSiteViewsToFilesystem()
    {
        // Setup
        $post1 = new \stdClass();
        $post1->content = 'content1';
        $post1->human_readable_url = 'url1';
        $post1->deleted_at = false;
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $post2->deleted_at = false;
        $post3 = new \stdClass();
        $post3->content = 'content3';
        $post3->human_readable_url = 'url3';
        $post3->deleted_at = true;
        $posts = [
            $post1, $post2, $post3
        ];
        $site = 'site1';

        $markdownConverter = Mockery::mock(MarkdownConverterInterface::class, function ($mock) use ($post1, $post2, $post3) {
            $mock->shouldReceive('convertToHtml')
                ->with($post1->content)
                ->times(1)
                ->andReturn('content1 with markup');
            $mock->shouldReceive('convertToHtml')
                ->with($post2->content)
                ->times(1)
                ->andReturn('content2 with markup');
            $mock->shouldReceive('convertToHtml')
                ->with($post3->content)
                ->times(0);
        });
        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($post1, $post2, $post3, $site) {
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$site.show", ['post' => $post1])
                ->times(1)
                ->andReturn('custom site: content1 with markup rendered in view');
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$site.show", ['post' => $post2])
                ->times(1)
                ->andReturn('custom site: content2 with markup rendered in view');
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$site.show", ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$site.list", ['posts' => [$post1, $post2]])
                ->times(1)
                ->andReturn('custom site: posts list rendered in view');
        });
        $filesystem = Mockery::mock(FilesystemInterface::class, function ($mock) {
            $mock->shouldReceive('put')
                ->with('url1', 'custom site: content1 with markup rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'custom site: content2 with markup rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url3', 'custom site: content3 with markup rendered in view')
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('index.html', 'custom site: posts list rendered in view')
                ->times(1)
                ->andReturn(true);
        });
        $publisher = new FilesystemPostPublisher($markdownConverter, $viewFactory, $filesystem);

        // Execute
        $result = $publisher->publish($posts, $site);

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
        $post1->deleted_at = false;
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $post2->deleted_at = false;
        $posts = [
            $post1, $post2
        ];

        $markdownConverter = Mockery::mock(MarkdownConverterInterface::class, function ($mock) use ($post1, $post2) {
            $mock->shouldReceive('convertToHtml')
                ->with($post1->content)
                ->times(1)
                ->andReturn('content1 with markup');
            $mock->shouldReceive('convertToHtml')
                ->with($post2->content)
                ->times(1)
                ->andReturn('content2 with markup');
        });
        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($post1, $post2) {
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post1])
                ->times(1)
                ->andReturn('content1 with markup rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post2])
                ->times(1)
                ->andReturn('content2 with markup rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.list', ['posts' => [$post1, $post2]])
                ->times(1)
                ->andReturn('posts list rendered in view');
        });
        $filesystem = Mockery::mock(FilesystemInterface::class, function ($mock) {
            $mock->shouldReceive('put')
                ->with('url1', 'content1 with markup rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'content2 with markup rendered in view')
                ->times(1)
                ->andReturn(false); // Failed
            $mock->shouldReceive('put')
                ->with('index.html', 'posts list rendered in view')
                ->times(1)
                ->andReturn(true);
        });
        $publisher = new FilesystemPostPublisher($markdownConverter, $viewFactory, $filesystem);

        // Execute
        $result = $publisher->publish($posts);

        // Assert
        $this->assertFalse($result);
    }
}
