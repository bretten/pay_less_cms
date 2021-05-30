@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Posts')

@section('content')
    <div class="container-fluid text-left">
        <span class="badge badge-pill badge-success">{{ session("site") ?? "All" }}</span>
        <div class="dropdown mt-3">
            <button class="btn btn-primary dropdown-toggle" id="site-dropdown" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Sites
            </button>
            <div class="dropdown-menu" aria-labelledby="site-dropdown">
                <a class="dropdown-item" href="/{{ Route::prefix(config('app.url_prefix'))->get('posts')->uri() . '?site=all' }}">All</a>
                @foreach ($sites as $site)
                    <a class="dropdown-item" href="/{{ Route::prefix(config('app.url_prefix'))->get('posts')->uri() . "?site=$site->domainName" }}">{{ $site->domainName }}</a>
                @endforeach
            </div>
        </div>
        <div class="mt-3">
            <form method="POST" action="/{{ Route::prefix(config('app.url_prefix'))->post('publish/site/' . session('site'))->uri() }}" id="publish-posts">
                @method('POST')
                @csrf
                <button type="submit" class="btn btn-info" onclick="return confirm('Publish site for {{ session("site") ?? "All" }}?')">Publish
                </button>
            </form>
        </div>
    </div>
    <div class="container-fluid text-right">
        <a class="btn btn-primary" href="/{{ Route::prefix(config('app.url_prefix'))->get('posts/create')->uri() }}" role="button">New Post</a>
    </div>
    <div class="container-fluid mt-3">
        <table class="table">
            <thead class="thead-dark">
            <tr>
                <th>Id</th>
                <th>Site</th>
                <th>Title</th>
                <th>Url</th>
                <th>Content</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($posts as $post)
                <tr>
                    <th>{{ $post->id }}</th>
                    <th>{{ $post->site }}</th>
                    <td><a href="/{{ Route::prefix(config('app.url_prefix'))->get("posts/$post->id")->uri() }}">{{ $post->title }}</a></td>
                    <td>{{ $post->humanReadableUrl }}</td>
                    <td>{{ $post->content }}</td>
                    <td>{{ $post->createdAt->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $post->updatedAt->format('Y-m-d H:i:s') }}</td>
                    <td><a class="btn btn-light" href="/{{ Route::prefix(config('app.url_prefix'))->get("posts/$post->id/edit")->uri() }}" role="button">Edit</a></td>
                    <td>
                        @if (!$post->deletedAt)
                            <form method="POST" action="/{{ Route::prefix(config('app.url_prefix'))->get("posts/$post->id")->uri() }}" id="post-{{ $post->id }}">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Please confirm the deletion:')">Delete
                                </button>
                            </form>
                        @else
                            <button class="btn btn-danger" disabled>Delete</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
