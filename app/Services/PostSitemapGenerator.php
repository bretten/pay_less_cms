<?php


namespace App\Services;


use App\Contracts\Models\Post;

interface PostSitemapGenerator
{
    /**
     * Should generate a sitemap for the specified Posts
     *
     * @param Post[] $posts
     * @param string $site
     * @return string
     */
    public function generateSitemap(iterable $posts, string $site);
}
