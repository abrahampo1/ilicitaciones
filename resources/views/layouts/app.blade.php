<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO & Meta Tags --}}
    <title>@yield('meta_title', $title ?? 'I-Licitaciones - Buscador de Licitaciones del Estado')</title>
    <meta name="description" content="@yield('meta_description', 'Plataforma avanzada para la búsqueda, visualización y análisis de licitaciones del sector público en España. Información detallada de organismos y adjudicaciones.')">
    <link rel="canonical" href="@yield('canonical', url()->current())" />
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:url" content="@yield('canonical', url()->current())" />
    <meta property="og:title" content="@yield('meta_title', $title ?? 'I-Licitaciones - Buscador de Licitaciones del Estado')" />
    <meta property="og:description" content="@yield('meta_description', 'Plataforma avanzada para la búsqueda, visualización y análisis de licitaciones del sector público en España.')" />
    <meta property="og:site_name" content="I-Licitaciones" />
    <meta property="og:locale" content="es_ES" />
    @hasSection('meta_image')
        <meta property="og:image" content="@yield('meta_image')" />
    @endif

    {{-- Twitter --}}
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="@yield('canonical', url()->current())" />
    <meta property="twitter:title" content="@yield('meta_title', $title ?? 'I-Licitaciones - Buscador de Licitaciones del Estado')" />
    <meta property="twitter:description" content="@yield('meta_description', 'Plataforma avanzada para la búsqueda, visualización y análisis de licitaciones del sector público en España.')" />
    @hasSection('meta_image')
        <meta property="twitter:image" content="@yield('meta_image')" />
    @endif

    {{-- Scripts & Styles --}}
    @vite('resources/css/app.css')

    @livewireStyles
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
        <header class="relative z-50" x-data="{ mobileOpen: false }">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" wire:navigate
                    class="italic text-xl hover:text-neutral-300 transition-colors shrink-0">I-Licitaciones</a>

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-neutral-400 hover:text-neutral-100 transition-colors">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Desktop nav --}}
                <nav aria-label="Navegación principal" class="hidden md:flex items-center gap-1 text-sm">
                    {{-- Contratos module --}}
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-neutral-300 hover:text-neutral-100 hover:bg-neutral-800/50 transition-all">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Contratos
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute top-full left-0 mt-1 w-52 bg-neutral-900 border border-neutral-700/50 rounded-xl shadow-2xl shadow-black/50 overflow-hidden">
                            <div class="p-1.5">
                                <a href="{{ route('home') }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-neutral-300 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                                    <span class="text-base">&#x25A3;</span> Dashboard
                                </a>
                                <a href="{{ route('contratos.index') }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-neutral-300 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                                    <span class="text-base">&#x25C7;</span> Contratos
                                </a>
                                <a href="{{ route('organismos.index') }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-neutral-300 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                                    <span class="text-base">&#x25CB;</span> Organismos
                                </a>
                                <a href="{{ route('empresas.index') }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-neutral-300 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                                    <span class="text-base">&#x25B3;</span> Empresas
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Presupuestos module --}}
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-neutral-500 hover:text-neutral-400 hover:bg-neutral-800/30 transition-all">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400/50"></span>
                            Presupuestos
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute top-full left-0 mt-1 w-52 bg-neutral-900 border border-neutral-700/50 rounded-xl shadow-2xl shadow-black/50 overflow-hidden">
                            <div class="p-4 text-center">
                                <p class="text-neutral-500 text-xs">Pr&oacute;ximamente</p>
                            </div>
                        </div>
                    </div>

                    {{-- Legislación module --}}
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-neutral-500 hover:text-neutral-400 hover:bg-neutral-800/30 transition-all">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400/50"></span>
                            Legislaci&oacute;n
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute top-full left-0 mt-1 w-52 bg-neutral-900 border border-neutral-700/50 rounded-xl shadow-2xl shadow-black/50 overflow-hidden">
                            <div class="p-4 text-center">
                                <p class="text-neutral-500 text-xs">Pr&oacute;ximamente</p>
                            </div>
                        </div>
                    </div>

                    {{-- Cargos module --}}
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-neutral-500 hover:text-neutral-400 hover:bg-neutral-800/30 transition-all">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400/50"></span>
                            Cargos
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute top-full left-0 mt-1 w-52 bg-neutral-900 border border-neutral-700/50 rounded-xl shadow-2xl shadow-black/50 overflow-hidden">
                            <div class="p-4 text-center">
                                <p class="text-neutral-500 text-xs">Pr&oacute;ximamente</p>
                            </div>
                        </div>
                    </div>

                    <div class="w-px h-5 bg-neutral-700/50 mx-1"></div>

                    <a href="https://github.com/abrahampo1/ilicitaciones" target="_blank" rel="noopener noreferrer"
                        class="px-3 py-2 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800/50 transition-all">
                        GitHub
                    </a>
                </nav>
            </div>

            {{-- Mobile nav --}}
            <nav x-show="mobileOpen" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="md:hidden mt-4 bg-neutral-900/95 border border-neutral-700/50 rounded-xl p-3 space-y-1 text-sm backdrop-blur-sm">

                {{-- Contratos section --}}
                <div x-data="{ expanded: true }">
                    <button @click="expanded = !expanded"
                        class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg text-neutral-200 hover:bg-neutral-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Contratos
                        </span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="expanded" x-collapse class="ml-4 space-y-0.5">
                        <a href="{{ route('home') }}" wire:navigate @click="mobileOpen = false"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                            <span>&#x25A3;</span> Dashboard
                        </a>
                        <a href="{{ route('contratos.index') }}" wire:navigate @click="mobileOpen = false"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                            <span>&#x25C7;</span> Contratos
                        </a>
                        <a href="{{ route('organismos.index') }}" wire:navigate @click="mobileOpen = false"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                            <span>&#x25CB;</span> Organismos
                        </a>
                        <a href="{{ route('empresas.index') }}" wire:navigate @click="mobileOpen = false"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800 transition-colors">
                            <span>&#x25B3;</span> Empresas
                        </a>
                    </div>
                </div>

                {{-- Future modules --}}
                <div class="flex items-center gap-2 px-3 py-2.5 text-neutral-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400/50"></span>
                    Presupuestos <span class="text-xs text-neutral-600 ml-auto">pr&oacute;ximamente</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-2.5 text-neutral-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400/50"></span>
                    Legislaci&oacute;n <span class="text-xs text-neutral-600 ml-auto">pr&oacute;ximamente</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-2.5 text-neutral-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400/50"></span>
                    Cargos <span class="text-xs text-neutral-600 ml-auto">pr&oacute;ximamente</span>
                </div>

                <div class="border-t border-neutral-800 pt-1 mt-1">
                    <a href="https://github.com/abrahampo1/ilicitaciones" target="_blank" rel="noopener noreferrer"
                        class="flex items-center gap-2 px-3 py-2.5 text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800 rounded-lg transition-colors">
                        GitHub
                    </a>
                </div>
            </nav>
        </header>

        <main id="contenido-principal" class="p-4">
            @yield('contenido')
            {{ $slot ?? '' }}
        </main>
    </div>

    @livewireScripts
</body>

</html>
