<?php


namespace App\Services;


use App\Contracts\Models\Post;
use Exception;
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
     * @var PostSitemapGenerator
     */
    private PostSitemapGenerator $sitemapGenerator;

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
     * @param PostSitemapGenerator $sitemapGenerator
     * @param string $resourcePath
     * @param int $pageSize
     */
    public function __construct(ViewFactoryContract $viewFactory, FilesystemInterface $sourceFilesystem,
                                SiteFilesystemFactoryInterface $destinationFilesystemFactory,
                                PostSitemapGenerator $sitemapGenerator,
                                string $resourcePath,
                                int $pageSize)
    {
        $this->viewFactory = $viewFactory;
        $this->sourceFilesystem = $sourceFilesystem;
        $this->destinationFilesystemFactory = $destinationFilesystemFactory;
        $this->sitemapGenerator = $sitemapGenerator;
        $this->resourcePath = $resourcePath;
        $this->pageSize = $pageSize;
    }

    /**
     * Publishes the specified Posts to the Filesystem
     *
     * @param Post[] $posts
     * @param string $site
     * @return array
     * @throws FileNotFoundException
     */
    public function publish(iterable $posts, string $site)
    {
        $this->viewFactory->addNamespace($site, $this->resourcePath . DIRECTORY_SEPARATOR . $site);

        $destinationFilesystem = $this->destinationFilesystemFactory->getSiteFilesystem($site);

        // Will hold the paths of all the files that were published to the destination filesystem
        $publishedFiles = [];

        // Publish posts
        $publishedFiles = array_merge($publishedFiles, $this->publishPosts($posts, $site, $destinationFilesystem));

        // Publish index files
        $publishedFiles = array_merge($publishedFiles, $this->publishPostIndexes($posts, $site, $destinationFilesystem));

        // Publish assets
        $publishedFiles = array_merge($publishedFiles, $this->publishAssets($site, $destinationFilesystem));

        // Publish sitemap
        $publishedFiles = array_merge($publishedFiles, $this->publishSitemap($posts, $site, $destinationFilesystem));

        // Remove stale files that weren't published in this execution
        $this->removeStaleFiles($publishedFiles, $destinationFilesystem);

        return $publishedFiles;
    }

    /**
     * Renders each Post's content and publishes to the destination filesystem as its own separate file.
     *
     * @param Post[] $posts
     * @param string $site
     * @param FilesystemInterface $destinationFilesystem
     * @return array
     * @throws Exception
     */
    private function publishPosts(iterable $posts, string $site, FilesystemInterface $destinationFilesystem)
    {
        $publishedFiles = [];

        foreach ($posts as $post) {

            if ($post->deletedAt) {
                try {
                    $destinationFilesystem->delete($post->humanReadableUrl);
                } catch (FileNotFoundException $e) {
                }
                continue;
            }

            // Publish the post
            if (false == $destinationFilesystem->put($post->humanReadableUrl, $this->renderPostContentView($post, $site), [
                    'mimetype' => 'text/html'
                ])
            ) {
                throw new Exception("Unable to publish post: $post->id, $post->humanReadableUrl");
            }

            // Add the post to the collection of published files
            $publishedFiles[] = $post->humanReadableUrl;
        }

        return $publishedFiles;
    }

    /**
     * Renders a list of all the Posts as an index file and publishes it to the destination filesystem. If the page
     * size is greater than 0, the Posts will be divided into separate Post list files.
     *
     * @param Post[] $posts
     * @param string $site
     * @param FilesystemInterface $destinationFilesystem
     * @return array
     * @throws Exception
     */
    private function publishPostIndexes(iterable $posts, string $site, FilesystemInterface $destinationFilesystem)
    {
        $publishedFiles = [];

        // Filter out deleted posts
        $posts = array_values(array_filter($posts, function ($post) {
            return !$post->deletedAt;
        }));

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

            // Write the index file to the filesystem
            if (false == $destinationFilesystem->put($fileName, $this->renderPostIndexView($page, $pagination, $site), [
                    'mimetype' => 'text/html'
                ])
            ) {
                throw new Exception("Unable to publish index: $fileName");
            }

            // Add the published file to the list of published files
            $publishedFiles[] = $fileName;
        }

        return $publishedFiles;
    }

    /**
     * Reads the asset files from the source filesystem and publishes them to the destination filesystem
     *
     * @param string $site
     * @param FilesystemInterface $destinationFilesystem
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    private function publishAssets(string $site, FilesystemInterface $destinationFilesystem)
    {
        $publishedFiles = [];

        // Get the assets to publish from the source filesystem
        $assetsToPublishPath = ($site ? $site . DIRECTORY_SEPARATOR : '') . 'assets';
        $files = $this->sourceFilesystem->listContents($assetsToPublishPath, true);

        // Publish each asset file
        foreach ($files as $file) {
            if ($file['type'] == 'dir') {
                continue;
            }

            // Determine the file path of the asset
            $fileName = 'assets' . str_replace($assetsToPublishPath, "", $file['dirname']) . DIRECTORY_SEPARATOR . $file['basename'];

            // Publish the asset to the destination filesystem
            if (false == $destinationFilesystem->put($fileName, $this->sourceFilesystem->read($file['path']))) {
                throw new Exception("Unable to publish asset: $fileName");
            }

            // Add the asset to the list of published files
            $publishedFiles[] = $fileName;
        }

        return $publishedFiles;
    }

    /**
     * Publishes the sitemap for all of the site's URLs
     *
     * @param iterable $posts
     * @param string $site
     * @param FilesystemInterface $destinationFilesystem
     * @return array
     * @throws Exception
     */
    private function publishSitemap(iterable $posts, string $site, FilesystemInterface $destinationFilesystem)
    {
        $publishedFiles = [];

        // Sitemap destination file path
        $sitemapPath = 'sitemap.xml';

        // Publish the sitemap
        if (false == $destinationFilesystem->put($sitemapPath, $this->sitemapGenerator->generateSitemap($posts, $site), [
                'mimetype' => 'application/xml'
            ])
        ) {
            throw new Exception("Unable to publish sitemap: $sitemapPath");
        }

        // Add the sitemap to the list of published files
        $publishedFiles[] = $sitemapPath;

        return $publishedFiles;
    }

    /**
     * Tries to render the Post with the view for the corresponding site. If no match is found, the Post
     * is rendered with the default view
     *
     * @param Post $post
     * @param string $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostContentView(Post $post, string $site)
    {
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
     * @param string $site
     * @return \Illuminate\Contracts\View\View
     */
    private function renderPostIndexView(iterable $posts, array $pagination, string $site)
    {
        try {
            return $this->viewFactory->make("$site::list", ['posts' => $posts, 'pagination' => $pagination]);
        } catch (InvalidArgumentException $e) {
            return $this->viewFactory->make('posts.published.list', ['posts' => $posts, 'pagination' => $pagination]);
        }
    }

    /**
     * Finds any stale files that were not in the list of published files and removes them from the
     * destination filesystem
     *
     * @param array $publishedFiles
     * @param FilesystemInterface $destinationFilesystem
     */
    private function removeStaleFiles(array $publishedFiles, FilesystemInterface $destinationFilesystem)
    {
        // List all files in destination filesystem
        $files = $destinationFilesystem->listContents('', true);

        // Delete files that are not in the collection of files that were published
        foreach ($files as $file) {
            if ($file['type'] == 'dir') {
                continue;
            }

            // If the file was in the collection of published files, do nothing
            if (in_array($file['path'], $publishedFiles)) {
                continue;
            }

            // If the file wasn't published, then remove it
            try {
                $destinationFilesystem->delete($file['path']);
            } catch (FileNotFoundException $e) {
            }
        }
    }
}
