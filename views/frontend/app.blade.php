<!doctype html>
<html @if($direction)dir="{{$direction}}"@endif @if($language)lang="{{$language}}" @endif>
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
{!! $head !!}
</head>

    <body>
        {!! $layout !!}

        <div id="modal"></div>
        <div id="alerts"></div>

        <script>
            document.getElementById('fk-loading').style.display = 'block';
            var forumkit = {extensions: {}};
        </script>

        {!! $js !!}

        <script id="json-payload" type="application/json">@json($payload)</script>

        <script>
            const data = JSON.parse(document.getElementById('json-payload').textContent);
            document.getElementById('fk-loading').style.display = 'none';

            try {
                forumkit.core.app.load(data);
                forumkit.core.app.bootExtensions(forumkit.extensions);
                forumkit.core.app.boot();
            } catch (e) {
                var error = document.getElementById('fk-loading-error');
                error.innerHTML += document.getElementById('fk-content').textContent;
                error.style.display = 'block';
                throw e;
            }
        </script>

        {!! $foot !!}
    </body>
</html>
