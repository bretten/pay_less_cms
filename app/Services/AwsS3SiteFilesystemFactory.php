<?php


namespace App\Services;


use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use OutOfRangeException;

class AwsS3SiteFilesystemFactory implements SiteFilesystemFactoryInterface
{
    /**
     * @var array
     */
    private array $siteBuckets;

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
     * @param array $siteBuckets
     * @param S3Client $s3Client
     */
    public function __construct(array $siteBuckets, S3Client $s3Client)
    {
        $this->siteBuckets = $siteBuckets;
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

        if (!array_key_exists($site, $this->siteBuckets)) {
            throw new OutOfRangeException("There is no bucket corresponding to site: $site");
        }

        $filesystem = new Filesystem(new AwsS3Adapter($this->s3Client, $this->siteBuckets[$site]));
        $this->filesystems[$site] = $filesystem;

        return $filesystem;
    }
}
