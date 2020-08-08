<?php


namespace App\Repositories;


use App\Post;

class EloquentPostRepository implements PostRepositoryInterface
{
    /**
     * @var Post $model
     */
    protected $model;

    /**
     * Constructor
     *
     * @param Post $model
     */
    public function __construct(Post $model)
    {
        $this->model = $model;
    }

    /**
     * Returns all Post rows
     *
     * @return Post[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->model->all();
    }
}
