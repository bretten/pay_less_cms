<?php


namespace App\Services;


use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use League\Flysystem\FilesystemInterface;

class FilesystemPostPublisher implements PostPublisherInterface
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var ViewFactoryContract
     */
    private $viewFactory;

    /**
     * Constructor
     *
     * @param FilesystemInterface $filesystem
     * @param ViewFactoryContract $viewFactory
     */
    public function __construct(FilesystemInterface $filesystem, ViewFactoryContract $viewFactory)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
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

        foreach ($posts as $post) {
            $result = $this->filesystem->put($post->human_readable_url, $this->viewFactory->make('posts.show', ['post' => $post]));

            if ($result == false) {
                $success = false;
            }
        }

        return $success;
    }
}
