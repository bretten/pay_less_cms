<?php

namespace App\Http\Controllers;

use App\Repositories\SiteRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SiteController extends Controller
{
    /**
     * @var SiteRepositoryInterface
     */
    private SiteRepositoryInterface $repository;

    /**
     * Constructor
     *
     * @param SiteRepositoryInterface $repository
     */
    public function __construct(SiteRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Displays all Sites
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sites = $this->repository->getAll();
        return response()->view('sites.index', ['sites' => $sites]);
    }

    /**
     * Display the page for creating a Site
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->view('sites.create');
    }

    /**
     * Persists the new Site
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $result = $this->repository->create(
            $request->input('domain_name'),
            $request->input('title')
        );
        if ($result) {
            return redirect(Route::prefix(config('app.url_prefix'))->get('sites')->uri());
        } else {
            return response('Server error', 500);
        }
    }

    /**
     * Displays the Site
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response(json_encode($this->repository->getByDomainName($id)));
    }

    /**
     * Display the page for editing a Site
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $site = $this->repository->getByDomainName($id);

        return response()->view('sites.edit', ['site' => $site]);
    }

    /**
     * Persists the edited Site
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $id)
    {
        $result = $this->repository->update(
            $id,
            $request->input('title'),
        );
        if ($result) {
            return redirect(Route::prefix(config('app.url_prefix'))->get('sites')->uri());
        } else {
            return response('Server error', 500);
        }
    }

    /**
     * Deletes the Site
     *
     * @param string $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        $result = $this->repository->delete($id);

        if ($result) {
            return redirect(Route::prefix(config('app.url_prefix'))->get('sites')->uri());
        } else {
            return response('Server error', 500);
        }
    }
}
