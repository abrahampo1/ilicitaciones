@extends('layouts.app')

@section('contenido')
    @section('meta_title', 'Organismos de Contratación Pública - I-Licitaciones')
    @section('meta_description', 'Directorio de ' . number_format($totalOrganismos, 0, ',', '.') . ' organismos de contratación pública en España. Volumen total licitado: ' . number_format($totalVolumen, 0, ',', '.') . '€. Filtra por provincia y categoría.')

    @push('json-ld')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "CollectionPage",
        "name": "Organismos de Contratación Pública en España",
        "description": "Directorio de organismos de contratación pública en España",
        "numberOfItems": {{ $totalOrganismos }}
    }
    </script>
    @endpush

    @push('pagination-links')
        @if($organismos->previousPageUrl())
            <link rel="prev" href="{{ $organismos->previousPageUrl() }}" />
        @endif
        @if($organismos->nextPageUrl())
            <link rel="next" href="{{ $organismos->nextPageUrl() }}" />
        @endif
    @endpush

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="text-xs text-neutral-500 flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:text-neutral-300 transition-colors">Inicio</a>
        <span>/</span>
        <span class="text-neutral-300">Organismos</span>
    </nav>
@endsection

    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h1 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Organismos de Contratación Pública
            </h1>
            <p class="text-neutral-400 mb-6">Instituciones y entidades contratantes del sector público</p>

            <div class="flex flex-wrap gap-4 mb-8">
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-400 text-xs uppercase tracking-wider">Total Organismos</span>
                    <p class="text-2xl font-mono text-cyan-400">{{ number_format($totalOrganismos, 0, ',', '.') }}</p>
                </div>
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-400 text-xs uppercase tracking-wider">Volumen Licitado</span>
                    <p class="text-2xl font-mono text-emerald-400">{{ number_format($totalVolumen, 0, ',', '.') }}&euro;</p>
                </div>
            </div>

            <!-- Search and Filters Form -->
            <form action="{{ route('organismos') }}" method="GET" class="max-w-4xl" role="search" aria-label="Buscar y filtrar organismos">
                <!-- Search Bar -->
                <div class="relative group mb-4">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-cyan-500 to-teal-500 rounded-xl opacity-20 group-hover:opacity-40 transition duration-200 blur"></div>
                    <div class="relative flex items-center bg-neutral-900 rounded-xl">
                        <label for="search-organismos" class="pl-4 text-neutral-400">
                            <span class="sr-only">Buscar organismo</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </label>
                        <input type="text"
                               id="search-organismos"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Buscar organismo por nombre..."
                               class="w-full bg-transparent border-none focus:ring-0 text-neutral-200 placeholder-neutral-400 py-3 pl-3 pr-4"
                        >
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Province Filter -->
                    <div class="relative">
                        <label for="provincia" class="block text-xs text-neutral-400 mb-2">Provincia</label>
                        <select id="provincia"
                                name="provincia"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="">Todas las provincias</option>
                            @foreach($provincias as $provincia)
                                <option value="{{ $provincia }}" {{ request('provincia') == $provincia ? 'selected' : '' }}>
                                    {{ $provincia }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="relative">
                        <label for="categoria-id" class="block text-xs text-neutral-400 mb-2">Categoría</label>
                        <select id="categoria-id"
                                name="categoria_id"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="">Todas las categorías</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Amount Min Filter -->
                    <div class="relative">
                        <label for="importe-min" class="block text-xs text-neutral-400 mb-2">Importe mínimo</label>
                        <input type="number"
                               id="importe-min"
                               name="importe_min"
                               value="{{ request('importe_min') }}"
                               placeholder="0"
                               step="1000"
                               class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 placeholder-neutral-400 py-2 px-4 focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Amount Max Filter -->
                    <div class="relative">
                        <label for="importe-max" class="block text-xs text-neutral-400 mb-2">Importe máximo</label>
                        <input type="number"
                               id="importe-max"
                               name="importe_max"
                               value="{{ request('importe_max') }}"
                               placeholder="Sin límite"
                               step="1000"
                               class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 placeholder-neutral-400 py-2 px-4 focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-3 mt-4">
                    <button type="submit"
                            class="px-6 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-colors">
                        Aplicar filtros
                    </button>
                    @if(request()->hasAny(['search', 'provincia', 'importe_min', 'importe_max', 'categoria_id']))
                        <a href="{{ route('organismos') }}"
                           class="px-6 py-2 bg-neutral-700 hover:bg-neutral-600 text-neutral-200 rounded-xl transition-colors">
                            Limpiar filtros
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Organismos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($organismos as $organismo)
            <a href="{{ route('organismo.show', $organismo->id) }}"
               class="group relative p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl hover:bg-neutral-800/60 hover:border-cyan-500/30 transition-all duration-300">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="font-light text-neutral-200 group-hover:text-white transition-colors line-clamp-2 mb-2">
                            {{ $organismo->nombre }}
                        </p>
                        @if($organismo->provincia)
                            <p class="text-xs text-neutral-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-neutral-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                {{ $organismo->provincia }}{{ $organismo->pais && $organismo->pais != 'España' ? ', ' . $organismo->pais : '' }}
                            </p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-mono text-emerald-400 group-hover:text-emerald-300 transition-colors">
                            {{ number_format($organismo->total_importe ?? 0, 0, ',', '.') }}&euro;
                        </p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $organismo->licitaciones_count }} licit.</p>
                    </div>
                </div>

                @if($organismo->sitio_web || $organismo->contacto_email || $organismo->contacto_telefono)
                    <div class="flex flex-wrap gap-2 mt-3 text-xs">
                        @if($organismo->sitio_web)
                            <span class="px-2 py-1 bg-neutral-700/30 rounded-lg text-neutral-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                Web
                            </span>
                        @endif
                        @if($organismo->contacto_email)
                            <span class="px-2 py-1 bg-neutral-700/30 rounded-lg text-neutral-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                Email
                            </span>
                        @endif
                        @if($organismo->contacto_telefono)
                            <span class="px-2 py-1 bg-neutral-700/30 rounded-lg text-neutral-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                Tel
                            </span>
                        @endif
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    <!-- Paginación -->
    <div class="mt-8 flex justify-center">
        {{ $organismos->links() }}
    </div>
@endsection
