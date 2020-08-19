@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Create a Post')

@section('content')
    <div class="container-fluid">
        <form method="POST" action="/{{ Route::prefix(config('app.url_prefix'))->get("posts")->uri() }}">
            @csrf

            <div class="form-group">
                <label for="post-title">Title</label>
                <input type="text" class="form-control" id="post-title" name="title">
            </div>

            <div class="form-group">
                <label for="post-content">Content</label>
                <textarea class="form-control" id="post-content" name="content" rows="10"></textarea>
            </div>

            <div class="form-group">
                <label for="post-human-readable-url">Human Readable URL</label>
                <input type="text" class="form-control" id="post-human-readable-url" name="human_readable_url">
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
            <a class="btn btn-light" href="/{{ Route::prefix(config('app.url_prefix'))->get("posts")->uri() }}" role="button">Cancel</a>
        </form>
    </div>
@endsection
