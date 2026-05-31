<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1758450566388837"
     crossorigin="anonymous"></script>

    {{-- Google Tag Manager. data-cfasync="false" evita que Cloudflare Rocket Loader
         reescriba el type (a "<token>-text/javascript") y deje el script sin ejecutar,
         que es lo que impedía a Google detectar la etiqueta. --}}
    <script data-cfasync="false">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5FZDH2L7');</script>
    {{-- End Google Tag Manager --}}

    {{-- SEO & Meta Tags --}}
    <meta name="google-adsense-account" content="ca-pub-1758450566388837">
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

    {{-- Pagination SEO --}}
    @stack('pagination-links')

    {{-- Feed RSS --}}
    <link rel="alternate" type="application/rss+xml" title="I-Licitaciones · Análisis" href="{{ route('analisis.feed') }}" />

    {{-- Scripts & Styles --}}
    @vite('resources/css/app.css')

    @stack('styles')
    @stack('json-ld')
</head>

<body class="bg-neutral-900 text-neutral-100 font-serif min-h-screen flex flex-col">
    {{-- Google Tag Manager (noscript) --}}
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5FZDH2L7"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    {{-- End Google Tag Manager (noscript) --}}

    {{-- Skip link para accesibilidad --}}
    <a href="#contenido-principal"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-neutral-100 focus:text-neutral-900 focus:px-4 focus:py-2 focus:rounded hidden">
        Saltar al contenido principal
    </a>

    <div class="flex-1 w-full text-neutral-100">
        <header class="border-b border-neutral-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <a href="{{ route('home') }}"
                    class="text-xl font-light tracking-tight hover:text-neutral-300 transition-colors">
                    <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent font-medium">I</span>-Licitaciones
                </a>
                <nav aria-label="Navegación principal" class="flex flex-wrap justify-center gap-1 text-sm">
                    <a href="{{ route('home') }}"
                        class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('home') ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50' }}">Inicio</a>
                    <a href="{{ route('organismos') }}"
                        class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('organismos', 'organismo.show') ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50' }}">Organismos</a>
                    <a href="{{ route('empresas') }}"
                        class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('empresas', 'empresa.show') ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50' }}">Empresas</a>
                    <a href="{{ route('analisis.index') }}"
                        class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('analisis.*') ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50' }}">Análisis</a>
                    <a href="https://github.com/abrahampo1/ilicitaciones" target="_blank" rel="noopener noreferrer"
                        class="px-3 py-1.5 rounded-lg text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50 transition-colors">GitHub</a>
                </nav>
            </div>
        </header>

        {{-- Breadcrumbs --}}
        @hasSection('breadcrumbs')
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                @yield('breadcrumbs')
            </div>
        @endif

        <main id="contenido-principal" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('contenido')
        </main>
    </div>

    <footer class="border-t border-neutral-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-neutral-500">
                <p>Datos de contratación pública en España · <a href="mailto:{{ config('periodico.contacto') }}" class="hover:text-neutral-300 transition-colors">{{ config('periodico.contacto') }}</a></p>
                <nav class="flex gap-4" aria-label="Navegación del pie de página">
                    <a href="{{ route('home') }}" class="hover:text-neutral-300 transition-colors">Inicio</a>
                    <a href="{{ route('organismos') }}" class="hover:text-neutral-300 transition-colors">Organismos</a>
                    <a href="{{ route('empresas') }}" class="hover:text-neutral-300 transition-colors">Empresas</a>
                    <a href="{{ route('analisis.index') }}" class="hover:text-neutral-300 transition-colors">Análisis</a>
                    <a href="{{ route('analisis.feed') }}" class="hover:text-neutral-300 transition-colors">RSS</a>
                    <a href="https://github.com/abrahampo1/ilicitaciones" target="_blank" rel="noopener noreferrer" class="hover:text-neutral-300 transition-colors">GitHub</a>
                </nav>
            </div>
        </div>
    </footer>
</body>

</html>
