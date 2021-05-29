<?php


namespace Tests\Unit\Repositories;

use App\Contracts\Models\Site as SiteContract;
use App\Models\Site;
use App\Repositories\EloquentSiteRepository;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Mock\EloquentMocker;

class EloquentSiteRepositoryTest extends TestCase
{
    /**
     * Tests that the repository can get all Site rows
     *
     * @return void
     */
    public function testGetAll()
    {
        // Setup
        $expectedSite1 = new SiteContract('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $expectedSite2 = new SiteContract('site1', 'title2', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $expectedSites = [
            $expectedSite1, $expectedSite2
        ];
        $eloquentSite1 = EloquentMocker::mockSite('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $eloquentSite2 = EloquentMocker::mockSite('site1', 'title2', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $eloquentSites = [
            $eloquentSite1, $eloquentSite2
        ];
        $builder = Mockery::mock(Builder::class, function ($mock) use ($eloquentSites) {
            $mock->shouldReceive('withTrashed')
                ->andReturn($mock);
            $mock->shouldReceive('get')
                ->andReturn($eloquentSites);
        });
        $site = Mockery::mock(Site::class, function ($mock) use ($builder) {
            $mock->shouldReceive('newQuery')
                ->andReturn($builder);
        });
        $repo = new EloquentSiteRepository($site);

        // Execute
        $result = $repo->getAll();

        // Assert
        $this->assertEquals($expectedSites, $result);
    }

    /**
     * Tests that the repository can get a Site by its domain name
     *
     * @return void
     */
    public function testGetByDomainName()
    {
        // Setup
        $expectedSite = new SiteContract('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $eloquentSite = EloquentMocker::mockSite('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null,);
        $site = Mockery::mock(Site::class, function ($mock) use ($eloquentSite) {
            $mock->shouldReceive('findOrFail')
                ->with("site1")
                ->andReturn($eloquentSite);
        });
        $repo = new EloquentSiteRepository($site);

        // Execute
        $result = $repo->getByDomainName("site1");

        // Assert
        $this->assertEquals($expectedSite, $result);
    }

    /**
     * Tests that the repository can create a Site
     *
     * @return void
     */
    public function testCreate()
    {
        // Setup
        $expected = true;
        $site = Mockery::mock(Site::class, function ($mock) use ($expected) {
            $mock->shouldReceive('setAttribute')
                ->with('domainName', 'site1')
                ->times(1);
            $mock->shouldReceive('setAttribute')
                ->with('title', 'title1')
                ->times(1);
            $mock->shouldReceive('save')
                ->andReturn($expected);
        });
        $repo = new EloquentSiteRepository($site);

        // Execute
        $result = $repo->create('site1', 'title1');

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the repository can edit a Site
     *
     * @return void
     */
    public function testEdit()
    {
        // Setup
        $domainName = "site1";
        $expected = true;
        $site = Mockery::mock(Site::class, function ($mock) use ($domainName, $expected) {
            $mock->shouldReceive('find')
                ->with($domainName)
                ->times(1)
                ->andReturn($mock);
            $mock->shouldReceive('setAttribute')
                ->with('title', 'title1 v2')
                ->times(1);
            $mock->shouldReceive('save')
                ->andReturn($expected);
        });
        $repo = new EloquentSiteRepository($site);

        // Execute
        $result = $repo->update('site1', 'title1 v2');

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the repository can delete a Site by its domain name
     *
     * @return void
     * @throws \Exception
     */
    public function testDelete()
    {
        // Setup
        $domainName = "site1";
        $site = Mockery::mock(Site::class, function ($mock) use ($domainName) {
            $mock->shouldReceive('find')
                ->with($domainName)
                ->times(1)
                ->andReturn($mock);
            $mock->shouldReceive('delete')
                ->times(1)
                ->andReturn(true);
        });
        $repo = new EloquentSiteRepository($site);

        // Execute
        $result = $repo->delete($domainName);

        // Assert
        $this->assertTrue($result);
    }
}
