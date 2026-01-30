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
    @vite('resources/css/app.css')

    @stack('styles')
    @stack('json-ld')
</head>

<body class="bg-neutral-900 text-neutral-100 font-serif">
    {{-- Skip link para accesibilidad --}}
    <a href="#contenido-principal"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-neutral-100 focus:text-neutral-900 focus:px-4 focus:py-2 focus:rounded hidden">
        Saltar al contenido principal
    </a>

    <div class="w-full h-full text-neutral-100 rounded-xl p-4">
        <header class="flex flex-col md:flex-row items-center justify-between gap-4">
            <a href="{{ route('home') }}"
                class="italic text-xl hover:text-neutral-300 transition-colors">I-Licitaciones</a>
            <nav aria-label="Navegación principal" class="flex flex-wrap justify-center gap-4 md:gap-6 text-sm">
                <a href="{{ route('home') }}"
                    class="text-neutral-300 hover:text-neutral-100 transition-colors">Inicio</a>
                <a href="{{ route('organismos') }}"
                    class="text-neutral-300 hover:text-neutral-100 transition-colors">Organismos</a>
                <a href="{{ route('empresas') }}"
                    class="text-neutral-300 hover:text-neutral-100 transition-colors">Empresas</a>
                <a href="https://github.com/abrahampo1/ilicitaciones" target="_blank" rel="noopener noreferrer"
                    class="text-neutral-300 hover:text-neutral-100 transition-colors">GitHub</a>
            </nav>
        </header>

        <main id="contenido-principal" class="p-4">
            @yield('contenido')
        </main>
    </div>
</body>

</html>
