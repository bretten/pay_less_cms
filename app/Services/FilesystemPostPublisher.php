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
    private string $siteViewPathStorage;

    /**
     * Constructor
     *
     * @param ViewFactoryContract $viewFactory
     * @param FilesystemInterface $sourceFilesystem
     * @param SiteFilesystemFactoryInterface $destinationFilesystemFactory
     * @param string $siteViewPathStorage
     */
    public function __construct(ViewFactoryContract $viewFactory, FilesystemInterface $sourceFilesystem,
                                SiteFilesystemFactoryInterface $destinationFilesystemFactory,
                                string $siteViewPathStorage)
    {
        $this->viewFactory = $viewFactory;
        $this->sourceFilesystem = $sourceFilesystem;
        $this->destinationFilesystemFactory = $destinationFilesystemFactory;
        $this->siteViewPathStorage = $siteViewPathStorage;
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
        $this->viewFactory->addNamespace($site, $this->siteViewPathStorage . DIRECTORY_SEPARATOR . $site);
        $success = true;

        $activePosts = [];

        $destinationFilesystem = $this->destinationFilesystemFactory->getSiteFilesystem($site);

        // Publish posts
        foreach ($posts as $post) {

            if ($post->deletedAt) {
                try {
                    $destinationFilesystem->delete($post->humanReadableUrl);
                } catch (FileNotFoundException $e) {
                }
                continue;
            }

            $success = $success && $destinationFilesystem->put($post->humanReadableUrl, $this->renderPostContentView($post, $site));

            array_push($activePosts, $post);
        }

        // Publish index file
        $success = $success && $destinationFilesystem->put('index.html', $this->renderPostIndexView($activePosts, $site));

        // Copy assets
        $destinationFilesystem->deleteDir('assets');
        $assetsToPublishPath = ($site ? 'sites' . DIRECTORY_SEPARATOR . $site : 'sites') . DIRECTORY_SEPARATOR . 'assets';
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
     * @param string|null $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostIndexView($posts, string $site = null)
    {
        if ($site == null) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts]);
        }

        try {
            return $this->viewFactory->make("$site::list", ['posts' => $posts]);
        } catch (InvalidArgumentException $e) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts]);
        }
    }
}
