@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Posts')

@section('content')
    <div class="container-fluid text-right">
        <a class="btn btn-primary" href="/{{ Route::prefix(config('app.url_prefix'))->get('posts/create')->uri() }}" role="button">New Post</a>
    </div>
    <div class="container-fluid mt-3">
        <table class="table">
            <thead class="thead-dark">
            <tr>
                <th>Id</th>
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
