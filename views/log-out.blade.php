@extends('forumkit.site::layouts.basic')

@section('title', $translator->trans('core.views.log_out.title'))

@section('content')
  <p>{{ $translator->trans('core.views.log_out.log_out_confirmation', ['site' => $settings->get('site_title')]) }}</p>

  <p>
    <a href="{{ $url }}" class="button">
      {{ $translator->trans('core.views.log_out.log_out_button') }}
    </a>
  </p>
@endsection
