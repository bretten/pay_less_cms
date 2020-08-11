<!doctype html>
<html lang="en">

<head>
    <title>@yield('page-title')</title>
    <meta name="Description"
          content="@yield('page-description')">

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
          integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
</head>

<body>
<div class="container-fluid text-center">
    <h1>@yield('page-header')</h1>
</div>

@yield('content')
</body>


</html>
