<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('admin_title', 'Redacción') · I-Licitaciones</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-neutral-900 text-neutral-100 font-sans min-h-screen flex flex-col">
    <header class="border-b border-neutral-800">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium">
                <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">Redacción</span>
                <span class="text-neutral-500">/ I-Licitaciones</span>
            </a>
            @auth
                <nav class="flex items-center gap-2 text-sm">
                    <a href="{{ route('admin.articles.index') }}"
                        class="px-3 py-1.5 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800/50 transition-colors">Artículos</a>
                    <a href="{{ route('admin.articles.create') }}"
                        class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors">Nuevo</a>
                    <a href="{{ route('home') }}"
                        class="px-3 py-1.5 rounded-lg text-neutral-400 hover:text-neutral-100 transition-colors">Ver sitio</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="px-3 py-1.5 rounded-lg text-neutral-400 hover:text-red-400 transition-colors">Salir</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-8">
        @if (session('ok'))
            <div class="mb-6 px-4 py-3 rounded-xl bg-emerald-500/10 text-emerald-300 text-sm">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 text-red-300 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('admin_content')
    </main>
</body>

</html>
