<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.0.4/jsoneditor.min.js"
        integrity="sha512-8ui6NfUrJH7tponbZd5Lai3dqToJ9x7rQQRqaNtdNuVdsuOkoTMEitV2jfRsJ3stEYjSsn8n+9nnvTv0i1hb7g=="
        crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.0.4/jsoneditor.min.css"
      integrity="sha512-PWaHjZQo6KuaDHCDvl1WEePqV8hGiJc4vzec7iH7dIX67ql/s3S47xRBptJfHfcffENdIp/pMHKY7rfkiE3Osw=="
      crossorigin="anonymous"/>

<script>
    var postContentElement = document.getElementById('post-content');
    var jsonEditorContainer = document.getElementById("jsoneditor");
    var jsonEditor = null;

    function initTinyMce() {
        tinymce.init({
            forced_root_block: false,
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

    function initJsonEditor() {
        var options = {
            "mode": "code",
            "indentation": 4,
            "onChangeText": function (jsonString) {
                postContentElement.value = jsonString;
            }
        };
        jsonEditor = new JSONEditor(jsonEditorContainer, options);
        jsonEditor.setText(JSON.stringify(JSON.parse(postContentElement.value), null, 4));
        jsonEditorContainer.style.display = 'block';
    }

    function removeJsonEditor() {
        if (jsonEditor != null) {
            jsonEditor.destroy();
        }
        jsonEditorContainer.style.display = 'none';
    }

    function changeRadio(radio) {
        var value = radio.value;
        if (value === 'plaintext') {
            removeTinyMce();
            removeJsonEditor();
        } else if (value === 'html') {
            initTinyMce();
            removeJsonEditor();
        } else if (value === 'json') {
            removeTinyMce();
            initJsonEditor();
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
