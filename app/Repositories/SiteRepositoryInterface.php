<?php


namespace App\Repositories;

use App\Contracts\Models\Site;

interface SiteRepositoryInterface
{
    /**
     * Should return all Sites
     *
     * @return Site[]
     */
    public function getAll();

    /**
     * Should return a Site by the specified domain name
     *
     * @param string $domainName
     * @return Site
     */
    public function getByDomainName(string $domainName);

    /**
     * Should create a new Site
     *
     * @param string $domainName
     * @param string $title
     * @return bool
     */
    public function create(string $domainName, string $title);

    /**
     * Should update a Site
     *
     * @param string $domainName
     * @param string $title
     * @return bool
     */
    public function update(string $domainName, string $title);

    /**
     * Should delete a Site
     *
     * @param string $domainName
     * @return bool
     */
    public function delete(string $domainName);
}
