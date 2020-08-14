<?php


namespace App\Services;


interface PostPublisherInterface
{
    /**
     * Should publish all specified Posts
     *
     * @param iterable $posts
     * @param string|null $site
     * @return bool
     */
    public function publish(iterable $posts, string $site = null);
}
