<?php


namespace Tests\Unit\Services;


use App\Contracts\Models\Site;
use App\Repositories\SiteRepositoryInterface;
use App\Services\AwsS3SiteFilesystemFactory;
use Aws\S3\S3Client;
use DateTime;
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
        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $site2 = new Site('site2', 'title2', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($site1, $site2) {
            $mock->shouldReceive('getByDomainName')
                ->with('site1')
                ->andReturns($site1);
            $mock->shouldReceive('getByDomainName')
                ->with('site2')
                ->andReturns($site2);
        });
        $factory = new AwsS3SiteFilesystemFactory($siteRepo, Mockery::mock(S3Client::class));

        // Execute
        $result = $factory->getSiteFilesystem('site1');
        $result2 = $factory->getSiteFilesystem('site2');

        // Assert
        $this->assertEquals('site1', $result->getAdapter()->getBucket());
        $this->assertEquals('site2', $result2->getAdapter()->getBucket());
    }

    /**
     * Tests that it will throw an exception for an unknown site
     *
     * @return void
     */
    public function testThrowsExceptionForUnknownSite()
    {
        // Setup
        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $site2 = null;
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($site1, $site2) {
            $mock->shouldReceive('getByDomainName')
                ->with('site1')
                ->andReturns($site1);
            $mock->shouldReceive('getByDomainName')
                ->with('site2')
                ->andReturns($site2);
        });
        $factory = new AwsS3SiteFilesystemFactory($siteRepo, Mockery::mock(S3Client::class));

        // Execute and Assert
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('There is no site for: site2');
        $factory->getSiteFilesystem('site2');
    }
}
