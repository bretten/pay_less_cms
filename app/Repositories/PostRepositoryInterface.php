<?php


namespace App\Repositories;


use App\Post;

interface PostRepositoryInterface
{
    /**
     * Should return all Posts
     *
     * @return Post[]
     */
    public function getAll();

    /**
     * Should return a Post by the specified ID
     *
     * @param $id
     * @return Post
     */
    public function getById($id);

    /**
     * Should create a new Post
     *
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function create(string $title, string $content, string $humanReadableUrl);
}
