<?php


namespace App\Services;


use App\Repositories\SiteRepositoryInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use OutOfRangeException;

class AwsS3SiteFilesystemFactory implements SiteFilesystemFactoryInterface
{
    /**
     * @var SiteRepositoryInterface
     */
    private SiteRepositoryInterface $siteRepository;

    /**
     * @var S3Client
     */
    private S3Client $s3Client;

    /**
     * @var array
     */
    private array $filesystems;

    /**
     * Constructor
     *
     * @param SiteRepositoryInterface $siteRepository
     * @param S3Client $s3Client
     */
    public function __construct(SiteRepositoryInterface $siteRepository, S3Client $s3Client)
    {
        $this->siteRepository = $siteRepository;
        $this->s3Client = $s3Client;
        $this->filesystems = [];
    }

    /**
     * Gets the AWS S3 filesystem for the specified site
     *
     * @param string|null $site
     * @return Filesystem|\League\Flysystem\FilesystemInterface|mixed
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

        $bucketName = $siteEntity->domainName;

        $filesystem = new Filesystem(new AwsS3Adapter($this->s3Client, $bucketName));
        $this->filesystems[$site] = $filesystem;

        return $filesystem;
    }
}
