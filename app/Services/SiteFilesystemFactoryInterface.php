<?php


namespace App\Services;


use League\Flysystem\FilesystemInterface;

interface SiteFilesystemFactoryInterface
{
    /**
     * Should return the Filesystem for the specified site
     *
     * @param string|null $site
     * @return FilesystemInterface
     */
    public function getSiteFilesystem(?string $site);
}
