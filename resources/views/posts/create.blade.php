<html>
<body>

<h1>Create a post</h1>

<form method="POST" action="/posts">
    @csrf

    <label for="post-title">Title</label>
    <input type="text" id="post-title" name="title">

    <br>
    <br>

    <label for="post-content">Content</label>
    <textarea id="post-content" name="content"></textarea>

    <br>
    <br>

    <label for="post-human-readable-url">Human Readable URL</label>
    <input type="text" id="post-human-readable-url" name="human_readable_url">

    <br>
    <br>

    <button type="submit">Submit</button>
</form>

</body>
</html>
