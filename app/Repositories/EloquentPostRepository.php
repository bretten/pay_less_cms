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

    /**
     * Creates a new Post with the specified parameters
     *
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function create(string $title, string $content, string $humanReadableUrl)
    {
        $this->model->title = $title;
        $this->model->content = $content;
        $this->model->human_readable_url = $humanReadableUrl;
        return $this->model->save();
    }
}
