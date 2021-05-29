@extends('layouts.app')

@section('page-title', 'Pay Less CMS')

@section('page-description', 'A simple CMS for static sites')

@section('page-header', 'Create a Site')

@section('content')


    <div class="container-fluid">
        <form method="POST" action="/{{ Route::prefix(config('app.url_prefix'))->get("sites")->uri() }}">
            @csrf

            <div class="form-group">
                <label for="site-domain-name">Domain Name</label>
                <input type="text" class="form-control" id="site-domain-name" name="domain_name">
            </div>

            <div class="form-group">
                <label for="site-title">Title</label>
                <input type="text" class="form-control" id="site-title" name="title">
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
            <a class="btn btn-light" href="/{{ Route::prefix(config('app.url_prefix'))->get("sites")->uri() }}"
               role="button">Cancel</a>
        </form>
    </div>
@endsection
