<?php


namespace App\Services;


use App\Contracts\Models\Post;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use InvalidArgumentException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

class FilesystemPostPublisher implements PostPublisherInterface
{
    /**
     * @var ViewFactoryContract
     */
    private ViewFactoryContract $viewFactory;

    /**
     * @var FilesystemInterface
     */
    private FilesystemInterface $sourceFilesystem;

    /**
     * @var SiteFilesystemFactoryInterface
     */
    private SiteFilesystemFactoryInterface $destinationFilesystemFactory;

    /**
     * @var string
     */
    private string $resourcePath;

    /**
     * @var int
     */
    private int $pageSize;

    /**
     * Constructor
     *
     * @param ViewFactoryContract $viewFactory
     * @param FilesystemInterface $sourceFilesystem
     * @param SiteFilesystemFactoryInterface $destinationFilesystemFactory
     * @param string $resourcePath
     * @param int $pageSize
     */
    public function __construct(ViewFactoryContract $viewFactory, FilesystemInterface $sourceFilesystem,
                                SiteFilesystemFactoryInterface $destinationFilesystemFactory,
                                string $resourcePath,
                                int $pageSize)
    {
        $this->viewFactory = $viewFactory;
        $this->sourceFilesystem = $sourceFilesystem;
        $this->destinationFilesystemFactory = $destinationFilesystemFactory;
        $this->resourcePath = $resourcePath;
        $this->pageSize = $pageSize;
    }

    /**
     * Publishes the specified Posts to the Filesystem
     *
     * @param Post[] $posts
     * @param string|null $site
     * @return bool
     * @throws FileNotFoundException
     */
    public function publish($posts, string $site = null)
    {
        $this->viewFactory->addNamespace($site, $this->resourcePath . DIRECTORY_SEPARATOR . $site);
        $success = true;

        $destinationFilesystem = $this->destinationFilesystemFactory->getSiteFilesystem($site);

        // Publish posts
        $success = $success && $this->publishPosts($destinationFilesystem, $posts, $site);

        // Publish index files
        $success = $success && $this->publishPostIndexes($destinationFilesystem, $posts, $site);

        // Copy assets
        $destinationFilesystem->deleteDir('assets');
        $assetsToPublishPath = ($site ? $site . DIRECTORY_SEPARATOR : '') . 'assets';
        $files = $this->sourceFilesystem->listContents($assetsToPublishPath, true);
        foreach ($files as $file) {
            if ($file['type'] == 'dir') {
                continue;
            }
            $success = $success && $destinationFilesystem->put('assets' . str_replace($assetsToPublishPath, "", $file['dirname']) . DIRECTORY_SEPARATOR . $file['basename'], $this->sourceFilesystem->read($file['path']));
        }

        return $success;
    }

    /**
     * Renders each Post's content and publishes to the destination filesystem as its own separate file.
     *
     * @param FilesystemInterface $destinationFilesystem
     * @param Post[] $posts
     * @param string|null $site
     * @return bool
     */
    private function publishPosts(FilesystemInterface $destinationFilesystem, $posts, string $site = null)
    {
        $success = true;

        foreach ($posts as $post) {

            if ($post->deletedAt) {
                try {
                    $destinationFilesystem->delete($post->humanReadableUrl);
                } catch (FileNotFoundException $e) {
                }
                continue;
            }

            $success = $success && $destinationFilesystem->put($post->humanReadableUrl, $this->renderPostContentView($post, $site));
        }

        return $success;
    }

    /**
     * Renders a list of all the Posts as an index file and publishes it to the destination filesystem. If the page
     * size is greater than 0, the Posts will be divided into separate Post list files.
     *
     * @param FilesystemInterface $destinationFilesystem
     * @param Post[] $posts
     * @param string|null $site
     * @return bool
     */
    private function publishPostIndexes(FilesystemInterface $destinationFilesystem, $posts, string $site = null)
    {
        $success = true;

        // Filter out deleted posts
        $posts = array_filter($posts, function ($post) {
            return !$post->deletedAt;
        });

        // Create index and following pages
        $pages = $this->pageSize > 0 ? array_chunk($posts, $this->pageSize) : [$posts];
        $pagination = [
            'total_pages' => count($pages),
            'page_size' => $this->pageSize,
            'current_page' => 1
        ];
        foreach ($pages as $i => $page) {
            $currentPage = $i + 1;
            $pagination['current_page'] = $currentPage;

            $fileName = $currentPage == 1 ? 'index.html' : "page_$currentPage.html";

            $success = $success && $destinationFilesystem->put($fileName, $this->renderPostIndexView($page, $pagination, $site));
        }

        return $success;
    }

    /**
     * Tries to render the Post with the view for the corresponding site. If no match is found, the Post
     * is rendered with the default view
     *
     * @param Post $post
     * @param string|null $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostContentView(Post $post, string $site = null)
    {
        if ($site == null) {
            return $this->viewFactory->make('posts.published.show', ['post' => $post]);
        }

        try {
            return $this->viewFactory->make("$site::show", ['post' => $post]);
        } catch (InvalidArgumentException $e) {
            return $this->viewFactory->make('posts.published.show', ['post' => $post]);
        }
    }

    /**
     * Tries to render the Posts with the view for the corresponding site. If no match is found, the Posts
     * are rendered with the default view
     *
     * @param Post[] $posts
     * @param array $pagination
     * @param string|null $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostIndexView($posts, array $pagination, string $site = null)
    {
        if ($site == null) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts, 'pagination' => $pagination]);
        }

        try {
            return $this->viewFactory->make("$site::list", ['posts' => $posts, 'pagination' => $pagination]);
        } catch (InvalidArgumentException $e) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts, 'pagination' => $pagination]);
        }
    }
}
