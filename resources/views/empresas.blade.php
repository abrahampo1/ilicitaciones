

@extends('layouts.app')

@section('contenido')
    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-cyan-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h1 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Empresas
            </h1>
            <p class="text-neutral-400 mb-6">Ranking de empresas por volumen de adjudicaciones</p>
            
            <div class="flex flex-wrap gap-4 mb-8">
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-400 text-xs uppercase tracking-wider">Total Empresas</span>
                    <p class="text-2xl font-mono text-sky-400">{{ number_format($totalEmpresas, 0, ',', '.') }}</p>
                </div>
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-400 text-xs uppercase tracking-wider">Volumen Adjudicado</span>
                    <p class="text-2xl font-mono text-emerald-400">{{ number_format($totalVolumen, 0, ',', '.') }}€</p>
                </div>
            </div>

            <!-- Search Form -->
            <form action="{{ route('empresas') }}" method="GET" class="max-w-lg" role="search" aria-label="Buscar empresas">
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-sky-500 to-cyan-500 rounded-xl opacity-20 group-hover:opacity-40 transition duration-200 blur"></div>
                    <div class="relative flex items-center bg-neutral-900 rounded-xl">
                        <label for="search-empresas" class="pl-4 text-neutral-400">
                            <span class="sr-only">Buscar empresa</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </label>
                        <input type="text"
                               id="search-empresas"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Buscar empresa por nombre o identificador..."
                               class="w-full bg-transparent border-none focus:ring-0 text-neutral-200 placeholder-neutral-400 py-3 pl-3 pr-4"
                        >
                        @if(request('search'))
                            <a href="{{ route('empresas') }}" class="pr-4 text-neutral-400 hover:text-neutral-300 transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Empresas -->
    <div class="relative">
        <div class="absolute -inset-1 bg-gradient-to-r from-sky-600/10 to-cyan-600/10 rounded-3xl blur-xl opacity-50"></div>
        <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
            <div class="space-y-1">
                @foreach ($empresas as $index => $item)
                    <a href="{{ route('empresa.show', $item->id) }}" 
                       class="group flex items-center py-4 px-4 -mx-4 rounded-xl hover:bg-neutral-800/50 transition-all duration-200 border-b border-neutral-800 last:border-none">
                        <span class="w-10 text-neutral-400 text-sm font-mono">
                            {{ str_pad($empresas->firstItem() + $index, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ $item->nombre }}
                            </p>
                            @if($item->identificador)
                                <p class="text-xs font-mono text-neutral-400 mt-0.5">{{ $item->identificador }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0 ml-4">
                            <p class="font-mono text-emerald-400 group-hover:text-emerald-300 transition-colors">
                                {{ number_format($item->total_importe, 0, ',', '.') }}€
                            </p>
                            <p class="text-xs text-neutral-400">{{ $item->total_adjudicaciones }} adj.</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Paginación -->
    <div class="mt-8 flex justify-center">
        {{ $empresas->links() }}
    </div>
@endsection
