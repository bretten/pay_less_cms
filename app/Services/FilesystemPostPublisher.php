<?php


namespace App\Services;


use League\Flysystem\FilesystemInterface;

class FilesystemPostPublisher implements PostPublisherInterface
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * Constructor
     *
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
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

        foreach ($posts as $post) {
            $result = $this->filesystem->put($post->human_readable_url, $post->content);

            if ($result == false) {
                $success = false;
            }
        }

        return $success;
    }
}
