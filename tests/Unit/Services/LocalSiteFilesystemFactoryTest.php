<?php


namespace Tests\Unit\Services;


use App\Services\LocalSiteFilesystemFactory;
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
        $rootDirs = [
            'site1' => '/home/vagrant/pay_less_cms/storage/app/published/site1-test/',
            'site2' => '/home/vagrant/pay_less_cms/storage/app/published/site2-test/'
        ];
        $factory = new LocalSiteFilesystemFactory($rootDirs);

        // Execute
        $result = $factory->getSiteFilesystem('site1');

        // Assert
        $this->assertEquals('/home/vagrant/pay_less_cms/storage/app/published/site1-test/', $result->getAdapter()->getPathPrefix());
    }

    /**
     * Tests that it will throw an exception for an unknown site
     *
     * @return void
     */
    public function testThrowsExceptionForUnknownSite()
    {
        // Setup
        $rootDirs = [
            'site1' => '/home/site1',
            'site2' => '/home/site2'
        ];
        $factory = new LocalSiteFilesystemFactory($rootDirs);

        // Execute and Assert
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('There is no root directory corresponding to site: site3');
        $factory->getSiteFilesystem('site3');
    }
}
