<?php


namespace App\Services;


use App\Contracts\Models\Post;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use InvalidArgumentException;
use League\CommonMark\MarkdownConverterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

class FilesystemPostPublisher implements PostPublisherInterface
{
    /**
     * @var MarkdownConverterInterface
     */
    private $markdownConverter;

    /**
     * @var ViewFactoryContract
     */
    private $viewFactory;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * Constructor
     *
     * @param MarkdownConverterInterface $markdownConverter
     * @param ViewFactoryContract $viewFactory
     * @param FilesystemInterface $filesystem
     */
    public function __construct(MarkdownConverterInterface $markdownConverter, ViewFactoryContract $viewFactory, FilesystemInterface $filesystem)
    {
        $this->markdownConverter = $markdownConverter;
        $this->viewFactory = $viewFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * Publishes the specified Posts to the Filesystem
     *
     * @param Post[] $posts
     * @param string|null $site
     * @return bool
     */
    public function publish($posts, string $site = null)
    {
        $success = true;

        $activePosts = [];

        foreach ($posts as $post) {

            if ($post->deletedAt) {
                try {
                    $this->filesystem->delete($post->humanReadableUrl);
                } catch (FileNotFoundException $e) {
                }
                continue;
            }

            $post->content = $this->markdownConverter->convertToHtml($post->content);
            $result = $this->filesystem->put($post->humanReadableUrl, $this->renderPostContentView($post, $site));

            if ($result == false) {
                $success = false;
            }
            array_push($activePosts, $post);
        }

        if (false == $this->filesystem->put('index.html', $this->renderPostIndexView($activePosts, $site))) {
            $success = false;
        }

        return $success;
    }

    /**
     * Tries to render the Post with the view for the corresponding site. If no match is found, the Post
     * is rendered with the default view
     *
     * @param Post $post
     * @param string|null $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostContentView(Post $post, string $site = null)
    {
        if ($site == null) {
            return $this->viewFactory->make('posts.published.show', ['post' => $post]);
        }

        try {
            return $this->viewFactory->make("posts.published.sites.$site.show", ['post' => $post]);
        } catch (InvalidArgumentException $e) {
            return $this->viewFactory->make('posts.published.show', ['post' => $post]);
        }
    }

    /**
     * Tries to render the Posts with the view for the corresponding site. If no match is found, the Posts
     * are rendered with the default view
     *
     * @param Post[] $posts
     * @param string|null $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostIndexView($posts, string $site = null)
    {
        if ($site == null) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts]);
        }

        try {
            return $this->viewFactory->make("posts.published.sites.$site.list", ['posts' => $posts]);
        } catch (InvalidArgumentException $e) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts]);
        }
    }
}
