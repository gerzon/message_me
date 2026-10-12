<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mini Messenger') · Mini Messenger</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class')">
    <div class="site-frame">
        <header class="topbar">
            <a class="brand" href="{{ route('messages.index') }}" aria-label="Mini Messenger home">
                <span class="brand-mark" aria-hidden="true">m</span>
                <span>mini<span class="brand-light">messenger</span></span>
            </a>
            <a class="topbar-link" href="{{ route('messages.index') }}">Your message board</a>
        </header>

        <main class="page-content">
            @if (session('status'))
                <div class="status-banner" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>

        <footer class="site-footer">A little space for the things you want to say.</footer>
    </div>
</body>
</html>
