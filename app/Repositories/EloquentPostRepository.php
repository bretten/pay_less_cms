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
        return $this->model->newQuery()->orderByDesc('created_at')->withTrashed()->get();
    }

    /**
     * Returns the Post specified by the ID
     *
     * @param $id
     * @return Post
     */
    public function getById($id)
    {
        return $this->model->findOrFail($id);
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

    /**
     * Updates the Post indicated by the ID with the new parameters
     *
     * @param $id
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function update($id, string $title, string $content, string $humanReadableUrl)
    {
        $this->model = $this->model->find($id);
        $this->model->title = $title;
        $this->model->content = $content;
        $this->model->human_readable_url = $humanReadableUrl;
        return $this->model->save();
    }

    /**
     * Deletes the Post indicated by the ID
     *
     * @param $id
     * @return bool|null
     * @throws \Exception
     */
    public function delete($id)
    {
        $this->model = $this->model->find($id);
        return $this->model->delete();
    }
}
