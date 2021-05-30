<?php


namespace App\Services;


use App\Repositories\SiteRepositoryInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use OutOfRangeException;

class LocalSiteFilesystemFactory implements SiteFilesystemFactoryInterface
{
    /**
     * @var string $publishedFilesRootPath
     */
    private string $publishedFilesRootPath;

    /**
     * @var SiteRepositoryInterface
     */
    private SiteRepositoryInterface $siteRepository;

    /**
     * @var array
     */
    private array $filesystems;

    /**
     * Constructor
     *
     * @param array $rootDirs
     */
    public function __construct(string $publishedFilesRootPath, SiteRepositoryInterface $siteRepository)
    {
        $this->publishedFilesRootPath = $publishedFilesRootPath;
        $this->siteRepository = $siteRepository;
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

        $siteEntity = $this->siteRepository->getByDomainName($site);
        if (!$siteEntity) {
            throw new OutOfRangeException("There is no site for: $site");
        }

        $publishPath = $this->publishedFilesRootPath . DIRECTORY_SEPARATOR . $siteEntity->domainName;

        $filesystem = new Filesystem(new Local($publishPath));
        $this->filesystems[$site] = $filesystem;

        return $filesystem;
    }
}
