<?php


namespace Tests\Unit\Services;


use App\Services\AwsS3SiteFilesystemFactory;
use Aws\S3\S3Client;
use Mockery;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

class AwsS3SiteFilesystemFactoryTest extends TestCase
{
    /**
     * Tests that it can get the filesystem for the specified site
     *
     * @return void
     */
    public function testGetSiteFilesystem()
    {
        // Setup
        $siteBuckets = [
            'site1' => 'bucket1',
            'site2' => 'bucket2'
        ];
        $factory = new AwsS3SiteFilesystemFactory($siteBuckets, Mockery::mock(S3Client::class));

        // Execute
        $result = $factory->getSiteFilesystem('site1');

        // Assert
        $this->assertEquals('bucket1', $result->getAdapter()->getBucket());
    }

    /**
     * Tests that it will throw an exception for an unknown site
     *
     * @return void
     */
    public function testThrowsExceptionForUnknownSite()
    {
        // Setup
        $siteBuckets = [
            'site1' => 'bucket1',
            'site2' => 'bucket2'
        ];
        $factory = new AwsS3SiteFilesystemFactory($siteBuckets, Mockery::mock(S3Client::class));

        // Execute and Assert
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('There is no bucket corresponding to site: site3');
        $factory->getSiteFilesystem('site3');
    }
}
