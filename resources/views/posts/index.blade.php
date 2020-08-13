@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Posts')

@section('content')
    <div class="container-fluid text-right">
        <a class="btn btn-primary" href="/posts/create" role="button">New Post</a>
    </div>
    <div class="container-fluid mt-3">
        <table class="table">
            <thead class="thead-dark">
            <tr>
                <th>Id</th>
                <th>Title</th>
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
                    <td><a href="/posts/{{ $post->id }}">{{ $post->title }}</a></td>
                    <td>{{ $post->content }}</td>
                    <td>{{ $post->created_at }}</td>
                    <td>{{ $post->updated_at }}</td>
                    <td><a class="btn btn-light" href="/posts/{{ $post->id }}/edit" role="button">Edit</a></td>
                    <td>
                        @if (!$post->deleted_at)
                            <form method="POST" action="/posts/{{ $post->id }}" id="post-{{ $post->id }}">
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
