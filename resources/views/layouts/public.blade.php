<!DOCTYPE html>
<html lang="vi" x-data="publicLayout()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'KeyLicense') }}</title>
    <meta name="description" content="{{ $description ?? 'KeyLicense' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none !important;}</style>
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    @yield('content')
    @stack('scripts')
</body>
</html>
