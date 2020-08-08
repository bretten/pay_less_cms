<html>
<body>

<table>
    <tr>
        <th>Id</th>
        <th>Title</th>
        <th>Content</th>
        <th>Created At</th>
        <th>Updated At</th>
    </tr>
    @foreach($posts as $post)
        <tr>
            <td>{{ $post->id }}</td>
            <td>{{ $post->title }}</td>
            <td>{{ $post->content }}</td>
            <td>{{ $post->created_at }}</td>
            <td>{{ $post->updated_at }}</td>
        </tr>
    @endforeach
</table>

</body>
</html>
