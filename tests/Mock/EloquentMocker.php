<?php


namespace Tests\Mock;


use App\Models\Post;
use App\Models\Site;
use DateTime;
use Mockery;

class EloquentMocker
{
    /**
     * Mocks an Eloquent Site
     *
     * @param string $domainName
     * @param string $title
     * @param DateTime $created_at
     * @param DateTime $updated_at
     * @param $deleted_at
     * @return Mockery\Mock
     */
    public static function mockSite(string $domainName, string $title, DateTime $created_at, DateTime $updated_at, $deleted_at)
    {
        return Mockery::mock(Site::class, function ($mock) use ($domainName, $title, $created_at, $updated_at, $deleted_at) {
            $mock->shouldReceive('getAttribute')->with('domainName')
                ->andReturn($domainName);
            $mock->shouldReceive('getAttribute')->with('title')
                ->andReturn($title);
            $mock->shouldReceive('getAttribute')->with('created_at')
                ->andReturn($created_at);
            $mock->shouldReceive('getAttribute')->with('updated_at')
                ->andReturn($updated_at);
            $mock->shouldReceive('getAttribute')->with('deleted_at')
                ->andReturn($deleted_at);
        })->makePartial();
    }

    /**
     * Mocks an Eloquent Post
     *
     * @param int $id
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $human_readable_url
     * @param DateTime $created_at
     * @param DateTime $updated_at
     * @param $deleted_at
     * @return Mockery\Mock
     */
    public static function mockPost(int $id, string $site, string $title, string $content, string $human_readable_url, DateTime $created_at, DateTime $updated_at, $deleted_at)
    {
        return Mockery::mock(Post::class, function ($mock) use ($id, $site, $title, $content, $human_readable_url, $created_at, $updated_at, $deleted_at) {
            $mock->shouldReceive('getAttribute')->with('id')
                ->andReturn($id);
            $mock->shouldReceive('getAttribute')->with('site')
                ->andReturn($site);
            $mock->shouldReceive('getAttribute')->with('title')
                ->andReturn($title);
            $mock->shouldReceive('getAttribute')->with('content')
                ->andReturn($content);
            $mock->shouldReceive('getAttribute')->with('human_readable_url')
                ->andReturn($human_readable_url);
            $mock->shouldReceive('getAttribute')->with('created_at')
                ->andReturn($created_at);
            $mock->shouldReceive('getAttribute')->with('updated_at')
                ->andReturn($updated_at);
            $mock->shouldReceive('getAttribute')->with('deleted_at')
                ->andReturn($deleted_at);
        })->makePartial();
    }
}
