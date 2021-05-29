<?php


namespace App\Repositories;


use App\Contracts\Models\Site as SiteContract;
use App\Models\Site;


class EloquentSiteRepository implements SiteRepositoryInterface
{
    /**
     * @var Site $model
     */
    protected $model;

    /**
     * Constructor
     *
     * @param Site $model
     */
    public function __construct(Site $model)
    {
        $this->model = $model;
    }

    /**
     * Returns all Sites
     */
    public function getAll()
    {
        $eloquentModels = $this->model->newQuery()->withTrashed()->get();
        $sites = [];
        foreach ($eloquentModels as $model) {
            $sites[] = $model->toSimple();
        }
        return $sites;
    }

    /**
     * Returns the Site by the domain name
     *
     * @param $domainName
     * @return SiteContract
     */
    public function getByDomainName($domainName)
    {
        return $this->model->findOrFail($domainName)->toSimple();
    }

    /**
     * Creates a Site
     *
     * @param string $domainName
     * @param string $title
     * @return bool
     */
    public function create(string $domainName, string $title)
    {
        $this->model->domainName = $domainName;
        $this->model->title = $title;
        return $this->model->save();
    }

    /**
     * Updates a Site
     *
     * @param string $domainName
     * @param string $title
     * @return bool
     */
    public function update(string $domainName, string $title)
    {
        $this->model = $this->model->find($domainName);
        $this->model->title = $title;
        return $this->model->save();
    }

    /**
     * Deletes a Site
     *
     * @param $domainName
     * @return bool
     */
    public function delete($domainName)
    {
        $this->model = $this->model->find($domainName);
        return $this->model->delete();
    }
}
