{!! $site['headerHtml'] !!}

<div id="app" class="App">

    <div id="app-navigation" class="App-navigation"></div>

    <div id="drawer" class="App-drawer">

        <header id="header" class="App-header">
            <div id="header-navigation" class="Header-navigation"></div>
            <div class="container">
                <div class="Header-title">
                    <a href="{{ $site['baseUrl'] }}" id="home-link">
                        @if ($site['logoUrl'])
                            <img src="{{ $site['logoUrl'] }}" alt="{{ $site['title'] }}" class="Header-logo">
                        @else
                            {{ $site['title'] }}
                        @endif
                    </a>
                </div>
                <div id="header-primary" class="Header-primary"></div>
                <div id="header-secondary" class="Header-secondary"></div>
            </div>
        </header>

    </div>

    <main class="App-content">
        <div id="content"></div>

        {!! $content !!}

        <div class="App-composer">
            <div class="container">
                <div id="composer"></div>
            </div>
        </div>
    </main>

</div>

{!! $site['footerHtml'] !!}
