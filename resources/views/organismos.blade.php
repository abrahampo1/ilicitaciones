

@extends('layouts.app')

@section('contenido')
    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h1 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Organismos
            </h1>
            <p class="text-neutral-400 mb-6">Instituciones y entidades contratantes</p>
            
            <div class="flex flex-wrap gap-4 mb-8">
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-400 text-xs uppercase tracking-wider">Total Organismos</span>
                    <p class="text-2xl font-mono text-cyan-400">{{ number_format($totalOrganismos, 0, ',', '.') }}</p>
                </div>
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-400 text-xs uppercase tracking-wider">Volumen Licitado</span>
                    <p class="text-2xl font-mono text-emerald-400">{{ number_format($totalVolumen, 0, ',', '.') }}€</p>
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
                            <p class="text-xs text-neutral-400">
                                📍 {{ $organismo->provincia }}{{ $organismo->pais && $organismo->pais != 'España' ? ', ' . $organismo->pais : '' }}
                            </p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-mono text-emerald-400 group-hover:text-emerald-300 transition-colors">
                            {{ number_format($organismo->total_importe ?? 0, 0, ',', '.') }}€
                        </p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $organismo->licitaciones_count }} licit.</p>
                    </div>
                </div>
                
                @if($organismo->sitio_web || $organismo->contacto_email || $organismo->contacto_telefono)
                    <div class="flex flex-wrap gap-2 mt-3 text-xs">
                        @if($organismo->sitio_web)
                            <span class="px-2 py-1 bg-neutral-700/30 rounded-lg text-neutral-400">🌐 Web</span>
                        @endif
                        @if($organismo->contacto_email)
                            <span class="px-2 py-1 bg-neutral-700/30 rounded-lg text-neutral-400">✉️ Email</span>
                        @endif
                        @if($organismo->contacto_telefono)
                            <span class="px-2 py-1 bg-neutral-700/30 rounded-lg text-neutral-400">📞 Tel</span>
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
