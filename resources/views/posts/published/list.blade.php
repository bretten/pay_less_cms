<!doctype html>
<html lang="en">

<head>
    <title>Posts</title>
    <meta name="Description"
          content="Custom description">

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
          integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
</head>

<body>
<div class="container-fluid text-center">
    <h1>Custom title</h1>
</div>

<div class="container-fluid mt-3">
    @foreach($posts as $post)
        <div class="card mt-5">
            <div class="card-header text-center">
                {{ $post->title }}
            </div>
            <div class="card-body">
                {!! $post->content !!}

                <p><a href="{{ $post->humanReadableUrl }}" class="btn btn-primary">Expand</a></p>
            </div>
            <div class="card-footer text-muted text-right">
                {{ $post->createdAt->format('Y-m-d H:i:s') }}
            </div>
        </div>
    @endforeach
</div>
</body>

</html>
