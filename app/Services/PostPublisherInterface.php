<?php


namespace App\Services;


interface PostPublisherInterface
{
    /**
     * Should publish all specified Posts
     *
     * @param iterable $posts
     * @return mixed
     */
    public function publish(iterable $posts);
}
