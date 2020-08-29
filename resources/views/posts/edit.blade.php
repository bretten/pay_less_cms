@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Edit a Post')

@section('content')


    <div class="container-fluid">
        <form method="POST" action="/{{ Route::prefix(config('app.url_prefix'))->get("posts/$post->id")->uri() }}">
            @method('PUT')
            @csrf

            <div class="form-group">
                <label for="post-site">Site</label>
                <input type="text" class="form-control" id="post-site" name="site" value="{{ $post->site }}">
            </div>

            <div class="form-group">
                <label for="post-title">Title</label>
                <input type="text" class="form-control" id="post-title" name="title" value="{{ $post->title }}">
            </div>

            <div id="jsoneditor" style="width:100%; height: 400px; display: none;"></div>
            <div class="form-group">
                <label for="post-content">Content</label>
                <textarea class="form-control" id="post-content" name="content"
                          rows="10">{{ $post->content }}</textarea>
            </div>
            @include('posts.shared.editor')

            <div class="form-group">
                <label for="post-human-readable-url">Human Readable URL</label>
                <input type="text" class="form-control" id="post-human-readable-url" name="human_readable_url"
                       value="{{ $post->humanReadableUrl }}">
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
            <a class="btn btn-light" href="/{{ Route::prefix(config('app.url_prefix'))->get("posts")->uri() }}" role="button">Cancel</a>
        </form>
    </div>
@endsection
