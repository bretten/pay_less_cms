<?php


namespace App\Repositories;


use App\Post;

interface PostRepositoryInterface
{
    /**
     * @return Post[]
     */
    public function getAll();
}
