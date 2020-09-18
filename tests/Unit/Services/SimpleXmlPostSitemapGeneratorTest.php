<?php


namespace Tests\Unit\Services;


use App\Contracts\Models\Post;
use App\Services\SimpleXmlPostSitemapGenerator;
use App\Support\DateTimeFactoryInterface;
use DateTime;
use Mockery;
use PHPUnit\Framework\TestCase;

class SimpleXmlPostSitemapGeneratorTest extends TestCase
{
    /**
     * Tests that a sitemap can be generated from Posts
     *
     * @return void
     */
    public function testGenerateSitemap()
    {
        // Setup
        $protocol = 'https';
        $site = 'site1.exampletld';
        $now = new DateTime('2020-09-17 02:01:01');
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $post1 = new Post(1, $site, 'title1', 'content1', 'url1-test-name.html', new DateTime('2020-09-17 01:01:01'), new DateTime('2020-09-17 01:01:01'), null);
        $post2 = new Post(2, $site, 'title2', 'content2', 'dir1/dir2/url2.html', new DateTime('2020-09-17 02:02:02'), new DateTime('2020-09-17 02:02:02'), null);
        $post3 = new Post(3, $site, 'title3', 'content3', 'url3.html', new DateTime('2020-09-17 03:03:03'), new DateTime('2020-09-17 03:03:03'), new DateTime('2020-09-17 03:03:03'));
        $post4 = new Post(4, $site, 'title4', 'content4', 'url4.html', new DateTime('2020-09-17 04:04:04'), new DateTime('2020-09-17 04:04:04'), null);
        $posts = [
            $post1, $post2, $post3, $post4
        ];
        $generator = new SimpleXmlPostSitemapGenerator($protocol, $dateTimeFactory);

        // Execute
        $result = $generator->generateSitemap($posts, $site);

        // Assert
        $actual = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://site1.exampletld</loc><lastmod>2020-09-17</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url><url><loc>https://www.site1.exampletld</loc><lastmod>2020-09-17</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url><url><loc>https://site1.exampletld/url1-test-name.html</loc><lastmod>2020-09-17</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url><url><loc>https://site1.exampletld/dir1/dir2/url2.html</loc><lastmod>2020-09-17</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url><url><loc>https://site1.exampletld/url4.html</loc><lastmod>2020-09-17</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url></urlset>
';
        $this->assertEquals($result, $actual);
    }
}
