<?php

namespace App\Console\Commands;

use App\Repositories\PostRepositoryInterface;
use App\Services\PostPublisherInterface;
use Illuminate\Console\Command;

class PublishPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish';

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
     * @return int
     */
    public function handle(PostRepositoryInterface $repo, PostPublisherInterface $publisher)
    {
        $posts = $repo->getAll();
        if (!$posts) {
            $this->warn("No posts to publish");
            return 0;
        }

        $result = $publisher->publish($posts);

        if ($result) {
            $this->line("Successfully published all posts");
            return 1;
        } else {
            // TODO: Provide more insight from publisher on what went wrong
            $this->error("Could not publish posts");
            return 0;
        }
    }
}
