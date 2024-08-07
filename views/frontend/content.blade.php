<div id="fk-loading" style="display: none">
    {{ $translator->trans('core.views.content.loading_text') }}
</div>

<noscript>
    <div class="Alert">
        <div class="container">
            {{ $translator->trans('core.views.content.javascript_disabled_message') }}
        </div>
    </div>
</noscript>

<div id="fk-loading-error" style="display: none">
    <div class="Alert">
        <div class="container">
            {{ $translator->trans('core.views.content.load_error_message') }}
        </div>
    </div>
</div>

<noscript id="fk-content">
    {!! $content !!}
</noscript>
