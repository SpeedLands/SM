<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/manifest.json">

<meta name="theme-color" content="#0ea5a4">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@if (file_exists(public_path('build/manifest.json')))
	@vite(['resources/css/app.css', 'resources/js/app.js'])
@else
	{{-- Fallback cuando no hay build: evita excepción en entornos sin `npm run build` --}}
	<link rel="stylesheet" href="{{ asset('css/app.css') }}">
	<script src="{{ asset('js/app.js') }}" defer></script>
@endif


@fluxAppearance