<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    function initTinyMce() {
        tinymce.init({
            selector: '#post-content',
            toolbar: 'image',
            plugins: 'image imagetools',
            automatic_uploads: true,
            images_upload_handler: function (blobInfo, success, failure, progress) {
                var site = document.getElementById('post-site').value;
                if (site == null || site === "") {
                    failure('Please specify the site');
                    return;
                }
                var xhr, formData;

                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', "/{{ Route::prefix(config('app.url_prefix'))->get('posts')->uri() }}");
                xhr.setRequestHeader('X-CSRF-Token', '{{ csrf_token() }}');

                xhr.upload.onprogress = function (e) {
                    progress(e.loaded / e.total * 100);
                };

                xhr.onload = function () {
                    var json;

                    if (xhr.status < 200 || xhr.status >= 300) {
                        failure('HTTP Error: ' + xhr.status);
                        return;
                    }

                    json = JSON.parse(xhr.responseText);

                    if (!json || typeof json.location != 'string') {
                        failure('Invalid JSON: ' + xhr.responseText);
                        return;
                    }

                    success(json.location);
                };

                xhr.onerror = function () {
                    failure('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                };

                formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                formData.append('site', site);

                xhr.send(formData);
            }
        });
    }

    function removeTinyMce() {
        tinymce.remove();
    }

    function changeRadio(radio) {
        var value = radio.value;
        if (value === 'plaintext') {
            removeTinyMce();
        } else if (value === 'html') {
            initTinyMce();
        } else if (value === 'json') {
            removeTinyMce();
        }
    }
</script>

<div class="container-fluid">
    <label class="btn btn-secondary active">
        <input type="radio" name="post-content-options" id="option-plain" onclick="changeRadio(this);" value="plaintext"
               checked> Plaintext
    </label>
    <label class="btn btn-secondary">
        <input type="radio" name="post-content-options" id="option-html" onclick="changeRadio(this);" value="html"> HTML
    </label>
    <label class="btn btn-secondary">
        <input type="radio" name="post-content-options" id="option-json" onclick="changeRadio(this);" value="json"> JSON
    </label>
</div>
