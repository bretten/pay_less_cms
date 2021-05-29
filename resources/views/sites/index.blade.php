@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Sites')

@section('content')
    <div class="container-fluid text-right">
        <a class="btn btn-primary" href="/{{ Route::prefix(config('app.url_prefix'))->get('sites/create')->uri() }}"
           role="button">New Site</a>
    </div>
    <div class="container-fluid mt-3">
        <table class="table">
            <thead class="thead-dark">
            <tr>
                <th>Id</th>
                <th>Title</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($sites as $site)
                <tr>
                    <th>{{ $site->domainName }}</th>
                    <th>{{ $site->title }}</th>
                    <td>{{ $site->createdAt->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $site->updatedAt->format('Y-m-d H:i:s') }}</td>
                    <td><a class="btn btn-light"
                           href="/{{ Route::prefix(config('app.url_prefix'))->get("sites/$site->domainName/edit")->uri() }}"
                           role="button">Edit</a></td>
                    <td>
                        @if (!$site->deletedAt)
                            <form method="POST"
                                  action="/{{ Route::prefix(config('app.url_prefix'))->get("sites/$site->domainName")->uri() }}"
                                  id="site-{{ $site->domainName }}">
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
