<?php


namespace App\Services;


use Illuminate\Contracts\View\Factory as ViewFactoryContract;
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
     * @param iterable $posts
     * @return bool
     */
    public function publish(iterable $posts)
    {
        $success = true;

        $activePosts = [];

        foreach ($posts as $post) {

            if ($post->deleted_at) {
                try {
                    $this->filesystem->delete($post->human_readable_url);
                } catch (FileNotFoundException $e) {
                }
                continue;
            }

            $post->content = $this->markdownConverter->convertToHtml($post->content);
            $result = $this->filesystem->put($post->human_readable_url, $this->viewFactory->make('posts.published.show', ['post' => $post]));

            if ($result == false) {
                $success = false;
            }
            array_push($activePosts, $post);
        }

        if (false == $this->filesystem->put('index.html', $this->viewFactory->make('posts.published.list', ['posts' => $activePosts]))) {
            $success = false;
        }

        return $success;
    }
}
