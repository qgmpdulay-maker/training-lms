{{--
    Browser tab title and icon, shared by every layout and the standalone
    auth pages. The name is written here rather than read from
    config('app.name'): that comes from APP_NAME in .env, which still holds
    Laravel's stock "Laravel" both locally and on the InfinityFree site, and
    .env never ships in the deploy zip.

    Pass $page for a "Training IMS — <page>" title.
--}}
<title>{{ __('Training IMS') }}{{ isset($page) ? ' — '.$page : '' }}</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('favicon-192.png') }}" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
