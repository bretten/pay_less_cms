<?php

namespace App\Http\Controllers;

use App\Repositories\PostRepositoryInterface;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @var PostRepositoryInterface
     */
    private $repository;

    /**
     * Constructor
     *
     * @param PostRepositoryInterface $repository
     */
    public function __construct(PostRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = $this->repository->getAll();

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
        $result = $this->repository->create(
            $request->input('title'),
            $request->input('content'),
            $request->input('human_readable_url')
        );
        if ($result) {
            return redirect('/posts');
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

        return response()->view('posts.show', ['post' => $post]);
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
            $request->input('title'),
            $request->input('content'),
            $request->input('human_readable_url')
        );
        if ($result) {
            return redirect('/posts');
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
            return redirect('/posts');
        } else {
            return response('Server error', 500);
        }
    }
}
