<?php


namespace App\Services;


use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use OutOfRangeException;

class LocalSiteFilesystemFactory implements SiteFilesystemFactoryInterface
{
    /**
     * @var array
     */
    private array $rootDirs;

    /**
     * @var array
     */
    private array $filesystems;

    /**
     * Constructor
     *
     * @param array $rootDirs
     */
    public function __construct(array $rootDirs)
    {
        $this->rootDirs = $rootDirs;
        $this->filesystems = [];
    }

    /**
     * Gets the local Filesystem for the specified site
     *
     * @param string|null $site
     * @return Filesystem|FilesystemInterface|mixed
     */
    public function getSiteFilesystem(?string $site)
    {
        if (array_key_exists($site, $this->filesystems)) {
            return $this->filesystems[$site];
        }

        if (!array_key_exists($site, $this->rootDirs)) {
            throw new OutOfRangeException("There is no root directory corresponding to site: $site");
        }

        $filesystem = new Filesystem(new Local($this->rootDirs[$site]));
        $this->filesystems[$site] = $filesystem;

        return $filesystem;
    }
}
