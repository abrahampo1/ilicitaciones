<div>
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
                    <p class="text-2xl font-mono text-emerald-400">{{ number_format($totalVolumen, 0, ',', '.') }}&euro;</p>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="max-w-4xl">
                <div class="relative group mb-4">
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
                               wire:model.live.debounce.300ms="search"
                               placeholder="Buscar empresa por nombre o identificador..."
                               class="w-full bg-transparent border-none focus:ring-0 text-neutral-200 placeholder-neutral-400 py-3 pl-3 pr-4">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <label for="importe-min" class="block text-xs text-neutral-400 mb-2">Importe mínimo</label>
                        <input type="number" id="importe-min" wire:model.live.debounce.500ms="importeMin"
                               placeholder="0" step="1000"
                               class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 placeholder-neutral-400 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>

                    <div class="relative">
                        <label for="importe-max" class="block text-xs text-neutral-400 mb-2">Importe máximo</label>
                        <input type="number" id="importe-max" wire:model.live.debounce.500ms="importeMax"
                               placeholder="Sin límite" step="1000"
                               class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 placeholder-neutral-400 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>

                    <div class="relative">
                        <label for="categoria-id" class="block text-xs text-neutral-400 mb-2">Categoría</label>
                        <select id="categoria-id" wire:model.live="categoriaId"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="">Todas las categorías</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($search || $categoriaId || $importeMin || $importeMax)
                    <div class="mt-4">
                        <button wire:click="clearFilters"
                                class="px-6 py-2 bg-neutral-700 hover:bg-neutral-600 text-neutral-200 rounded-xl transition-colors">
                            Limpiar filtros
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Lista de Empresas -->
    <div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity">
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-sky-600/10 to-cyan-600/10 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="space-y-1">
                    @foreach ($empresas as $index => $item)
                        <a href="{{ route('empresas.show', $item->id) }}" wire:navigate wire:key="emp-{{ $item->id }}"
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
                                    {{ number_format($item->total_importe, 0, ',', '.') }}&euro;
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
    </div>
</div>
