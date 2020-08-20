<?php


namespace App\Repositories;


use App\Contracts\Models\Post;

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
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function create(string $site, string $title, string $content, string $humanReadableUrl);

    /**
     * Should update a Post
     *
     * @param $id
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function update($id, string $site, string $title, string $content, string $humanReadableUrl);

    /**
     * Should delete a Post
     *
     * @param $id
     * @return bool
     */
    public function delete($id);
}
