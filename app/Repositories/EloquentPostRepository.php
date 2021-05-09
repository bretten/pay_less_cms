<?php


namespace App\Repositories;


use App\Contracts\Models\Post as PostContract;
use App\Models\Post;

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
     * @return PostContract[]
     */
    public function getAll()
    {
        $eloquentModels = $this->model->newQuery()->withTrashed()->get();
        $posts = [];
        foreach ($eloquentModels as $model) {
            $posts[] = $model->toSimple();
        }
        return $posts;
    }

    /**
     * Returns the Post specified by the ID
     *
     * @param $id
     * @return PostContract
     */
    public function getById($id)
    {
        return $this->model->findOrFail($id)->toSimple();
    }

    /**
     * Creates a new Post with the specified parameters
     *
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function create(string $site, string $title, string $content, string $humanReadableUrl)
    {
        $this->model->site = $site;
        $this->model->title = $title;
        $this->model->content = $content;
        $this->model->human_readable_url = $humanReadableUrl;
        return $this->model->save();
    }

    /**
     * Updates the Post indicated by the ID with the new parameters
     *
     * @param $id
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function update($id, string $site, string $title, string $content, string $humanReadableUrl)
    {
        $this->model = $this->model->find($id);
        $this->model->site = $site;
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
