<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', '사주 앱') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <h1 class="text-xl font-bold text-indigo-600">
                <a href="{{ url('/') }}">{{ config('app.name', '사주 앱') }}</a>
            </h1>
        </div>
    </header>

    <main class="py-12 px-4">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} {{ config('app.name', '사주 앱') }}. All rights reserved.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
