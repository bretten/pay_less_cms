<?php

namespace App\Http\Controllers;

use App\Contracts\Models\Post;
use App\Repositories\PostRepositoryInterface;
use App\Services\SiteFilesystemFactoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

class PostController extends Controller
{
    /**
     * @var PostRepositoryInterface
     */
    private $repository;

    /**
     * @var SiteFilesystemFactoryInterface
     */
    private $siteFileSystemFactory;

    /**
     * Constructor
     *
     * @param PostRepositoryInterface $repository
     * @param SiteFilesystemFactoryInterface $siteFilesystemFactory
     */
    public function __construct(PostRepositoryInterface $repository, SiteFilesystemFactoryInterface $siteFilesystemFactory)
    {
        $this->repository = $repository;
        $this->siteFileSystemFactory = $siteFilesystemFactory;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $posts = $this->repository->getAll();
        usort($posts, function (Post $a, Post $b) {
            return $b->createdAt->getTimestamp() - $a->createdAt->getTimestamp();
        });

        if ($request->query("site") != null) {
            $site = $request->query("site");
            if ($site == 'all') {
                $request->session()->forget('site');
            } else {
                $request->session()->put('site', $site);
            }
        }

        if ($request->session()->exists('site')) {
            $site = $request->session()->get('site');
            $posts = array_filter($posts, function ($post) use ($site) {
                return $post->site == $site;
            });
        }

        return response()->view('posts.index', ['posts' => $posts]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            $site = $request->input('site');
            $filesystem = $this->siteFileSystemFactory->getSiteFilesystem($site);
            $file = $request->file('file');
            $path = 'assets' . DIRECTORY_SEPARATOR . $file->getClientOriginalName();
            if ($file->getClientMimeType() == "image/jpeg" && $file->get() != '') {
                imagejpeg(imagecreatefromjpeg($file->path()), $file->path(), 80);
            } else if ($file->getClientMimeType() == "image/gif" && $file->get() != '') {
                imagegif(imagecreatefromgif($file->path()), $file->path(), 80);
            } else if ($file->getClientMimeType() == "image/png" && $file->get() != '') {
                imagepng(imagecreatefrompng($file->path()), $file->path(), 80);
            }
            $filesystem->put($path, $file->get());
            if ($filesystem->getAdapter() instanceof AwsS3Adapter) {
                $uploadedPath = $filesystem->getAdapter()->getClient()->getObjectUrl($site, $path);
            } else {
                $uploadedPath = DIRECTORY_SEPARATOR . $path;
            }

            return response("{\"location\": \"$uploadedPath\"}", 200);
        }

        $result = $this->repository->create(
            $request->input('site'),
            $request->input('title'),
            $request->input('content'),
            $request->input('human_readable_url')
        );
        if ($result) {
            return redirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
        } else {
            return response('Server error', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = $this->repository->getById($id);

        return response()->view('posts.published.show', ['post' => $post]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = $this->repository->getById($id);

        return response()->view('posts.edit', ['post' => $post]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $id)
    {
        $result = $this->repository->update(
            $id,
            $request->input('site'),
            $request->input('title'),
            $request->input('content'),
            $request->input('human_readable_url')
        );
        if ($result) {
            return redirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
        } else {
            return response('Server error', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        $result = $this->repository->delete($id);

        if ($result) {
            return redirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
        } else {
            return response('Server error', 500);
        }
    }
}
