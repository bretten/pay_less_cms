<?php

namespace App\Console\Commands;

use App\Contracts\Models\Post;
use App\Repositories\PostRepositoryInterface;
use App\Repositories\SiteRepositoryInterface;
use App\Services\PostPublisherInterface;
use Illuminate\Console\Command;

class PublishPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish {--site=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes posts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command that publishes the Posts
     *
     * @param PostRepositoryInterface $repo
     * @param PostPublisherInterface $publisher
     * @return void
     */
    public function handle(PostRepositoryInterface $repo, SiteRepositoryInterface $siteRepo, PostPublisherInterface $publisher)
    {
        $siteOption = $this->option('site') ? $this->option('site') : null;
        if ($siteOption) {
            $sites = [
                $siteRepo->getByDomainName($siteOption)
            ];
        } else {
            $sites = $siteRepo->getAll();
        }

        $allPosts = $repo->getAll();
        if (!$allPosts) {
            $this->warn("No posts to publish");
            return;
        }

        foreach ($sites as $site) {
            if ($site->deletedAt) {
                $this->info("Skipping $site->domainName because it is deleted");
                continue;
            }
            $this->publishSite($publisher, $site->domainName, $allPosts);
        }
    }

    /**
     * Filters the Posts by the specified site and then publishes the Posts
     *
     * @param PostPublisherInterface $publisher
     * @param string $site
     * @param array $allPosts
     * @return bool
     */
    private function publishSite(PostPublisherInterface $publisher, string $site, array $allPosts)
    {
        // Get posts for the site
        $sitePosts = array_filter($allPosts, function ($post) use ($site) {
            return $post->site == $site;
        });

        // Order by creation date desc
        usort($sitePosts, function (Post $a, Post $b) {
            return $b->createdAt->getTimestamp() - $a->createdAt->getTimestamp();
        });

        $result = $publisher->publish($sitePosts, $site);

        if ($result) {
            $this->line("Successfully published " . count($sitePosts) . " posts for $site");
            return true;
        } else {
            // TODO: Provide more insight from publisher on what went wrong
            $this->error("Could not publish posts for $site");
            return false;
        }
    }
}
