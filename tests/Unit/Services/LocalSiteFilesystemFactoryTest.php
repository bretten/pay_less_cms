<?php


namespace Tests\Unit\Services;


use App\Contracts\Models\Site;
use App\Repositories\SiteRepositoryInterface;
use App\Services\LocalSiteFilesystemFactory;
use DateTime;
use Mockery;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

class LocalSiteFilesystemFactoryTest extends TestCase
{
    /**
     * Tests that it can get the filesystem for the specified site
     *
     * @return void
     */
    public function testGetSiteFilesystem()
    {
        // TODO: Test is dependent upon dev environment. LocalSiteFilesystemFactory instantiates Filesystem with Local adapter that tries to create a directory
        $this->markAsRisky();

        // Setup
        $rootDir = '/home/vagrant/pay_less_cms/storage/app/published';
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
        $factory = new LocalSiteFilesystemFactory($rootDir, $siteRepo);

        // Execute
        $result = $factory->getSiteFilesystem('site1');
        $result2 = $factory->getSiteFilesystem('site2');

        // Assert
        $this->assertEquals('/home/vagrant/pay_less_cms/storage/app/published/site1/', $result->getAdapter()->getPathPrefix());
        $this->assertEquals('/home/vagrant/pay_less_cms/storage/app/published/site2/', $result2->getAdapter()->getPathPrefix());
    }

    /**
     * Tests that it will throw an exception for an unknown site
     *
     * @return void
     */
    public function testThrowsExceptionForUnknownSite()
    {
        // Setup
        $rootDir = '/home/vagrant/pay_less_cms/storage/app/published';
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
        $factory = new LocalSiteFilesystemFactory($rootDir, $siteRepo);

        // Execute and Assert
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('There is no site for: site2');
        $factory->getSiteFilesystem('site2');
    }
}
