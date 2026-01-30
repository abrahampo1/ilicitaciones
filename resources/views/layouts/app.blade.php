<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO & Meta Tags --}}
    <title>@yield('meta_title', 'I-Licitaciones - Buscador de Licitaciones del Estado')</title>
    <meta name="description" content="@yield('meta_description', 'Plataforma avanzada para la búsqueda, visualización y análisis de licitaciones del sector público en España. Información detallada de organismos y adjudicaciones.')">
    <link rel="canonical" href="@yield('canonical', url()->current())" />
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:url" content="@yield('canonical', url()->current())" />
    <meta property="og:title" content="@yield('meta_title', 'I-Licitaciones - Buscador de Licitaciones del Estado')" />
    <meta property="og:description" content="@yield('meta_description', 'Plataforma avanzada para la búsqueda, visualización y análisis de licitaciones del sector público en España.')" />
    <meta property="og:site_name" content="I-Licitaciones" />
    <meta property="og:locale" content="es_ES" />
    @hasSection('meta_image')
        <meta property="og:image" content="@yield('meta_image')" />
    @endif

    {{-- Twitter --}}
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="@yield('canonical', url()->current())" />
    <meta property="twitter:title" content="@yield('meta_title', 'I-Licitaciones - Buscador de Licitaciones del Estado')" />
    <meta property="twitter:description" content="@yield('meta_description', 'Plataforma avanzada para la búsqueda, visualización y análisis de licitaciones del sector público en España.')" />
    @hasSection('meta_image')
        <meta property="twitter:image" content="@yield('meta_image')" />
    @endif

    {{-- Scripts & Styles --}}
    <script src="https://cdn.tailwindcss.com"></script>

    @stack('styles')
    @stack('json-ld')
</head>

<body class="bg-neutral-900 font-serif">
    <div class="w-full h-full text-neutral-100 rounded-xl p-4">
        <div class="text-neutral-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <h1 class="italic text-xl">I-Licitaciones</h1>
            <nav class="flex flex-wrap justify-center gap-4 md:gap-6 text-sm">
                <a href="{{ route('home') }}" class="text-neutral-400 hover:text-neutral-100 transition-colors">Home</a>
                <a href="{{ route('organismos') }}"
                    class="text-neutral-400 hover:text-neutral-100 transition-colors">Organismos</a>
                <a href="{{ route('empresas') }}"
                    class="text-neutral-400 hover:text-neutral-100 transition-colors">Empresas</a>
                <a href="https://github.com/abrahampo1/ilicitaciones" target="_blank"
                    class="text-neutral-400 hover:text-neutral-100 transition-colors">GitHub</a>
            </nav>
        </div>
        <div class="p-4">
            @yield('contenido')
        </div>
    </div>
</body>

</html>
