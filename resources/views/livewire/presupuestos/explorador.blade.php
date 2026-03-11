<div>
    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-blue-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h1 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Explorador
            </h1>
            <p class="text-neutral-400 mb-6">Navega y filtra las partidas presupuestarias</p>

            <!-- Search Bar -->
            <div class="max-w-4xl">
                <div class="relative group mb-4">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-sky-500 to-blue-500 rounded-xl opacity-20 group-hover:opacity-40 transition duration-200 blur"></div>
                    <div class="relative flex items-center bg-neutral-900 rounded-xl">
                        <label for="search-presupuestos" class="pl-4 text-neutral-400">
                            <span class="sr-only">Buscar partidas</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </label>
                        <input type="text"
                               id="search-presupuestos"
                               wire:model.live.debounce.300ms="search"
                               placeholder="Buscar por c&oacute;digo o entidad..."
                               class="w-full bg-transparent border-none focus:ring-0 text-neutral-200 placeholder-neutral-400 py-3 pl-3 pr-4">
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <!-- Ejercicio -->
                    <div>
                        <label for="filter-ejercicio" class="block text-xs text-neutral-400 mb-2">Ejercicio</label>
                        <select id="filter-ejercicio" wire:model.live="ejercicio"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                            <option value="">Todos</option>
                            @foreach($ejercicios as $ej)
                                <option value="{{ $ej }}">{{ $ej }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tipo presupuesto -->
                    <div>
                        <label for="filter-tipo" class="block text-xs text-neutral-400 mb-2">Tipo</label>
                        <select id="filter-tipo" wire:model.live="tipoPresupuesto"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                            <option value="gastos">Gastos</option>
                            <option value="ingresos">Ingresos</option>
                        </select>
                    </div>

                    <!-- Tipo entidad -->
                    <div>
                        <label for="filter-entidad-tipo" class="block text-xs text-neutral-400 mb-2">Nivel</label>
                        <select id="filter-entidad-tipo" wire:model.live="entidadTipo"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                            <option value="">Todos</option>
                            <option value="estado">Estado</option>
                            <option value="ccaa">CCAA</option>
                            <option value="municipio">Municipio</option>
                        </select>
                    </div>

                    <!-- Entidad específica -->
                    @if($entidades->isNotEmpty())
                        <div>
                            <label for="filter-entidad" class="block text-xs text-neutral-400 mb-2">Entidad</label>
                            <select id="filter-entidad" wire:model.live="entidadId"
                                    class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                                <option value="">Todas</option>
                                @foreach($entidades as $id => $nombre)
                                    <option value="{{ $id }}">{{ Str::limit($nombre, 30) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Capítulo -->
                    <div>
                        <label for="filter-capitulo" class="block text-xs text-neutral-400 mb-2">Cap&iacute;tulo</label>
                        <select id="filter-capitulo" wire:model.live="capitulo"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                            <option value="">Todos</option>
                            @foreach($capituloLabels as $code => $label)
                                <option value="{{ $code }}">{{ $code }}. {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($search || $ejercicio || $entidadTipo || $entidadId || $capitulo)
                    <div class="mt-4">
                        <button wire:click="clearFilters"
                                class="px-4 py-2 bg-neutral-700 hover:bg-neutral-600 text-neutral-200 rounded-xl transition-colors text-sm">
                            Limpiar filtros
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Resumen por capítulo (barras horizontales) -->
    @if($resumenCapitulos && count($resumenCapitulos) > 0)
        @php $maxCap = $resumenCapitulos->max('total') ?: 1; @endphp
        <div class="mb-8 p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
            <h3 class="text-sm text-neutral-400 uppercase tracking-wider mb-4">Distribuci&oacute;n por cap&iacute;tulo</h3>
            <div class="space-y-2">
                @foreach($resumenCapitulos as $cap)
                    @php
                        $pct = round(($cap->total / $maxCap) * 100);
                        $label = $capituloLabels[$cap->capitulo] ?? "Cap. {$cap->capitulo}";
                    @endphp
                    <button wire:click="$set('capitulo', '{{ $cap->capitulo }}')"
                            class="w-full text-left group">
                        <div class="flex justify-between text-xs mb-0.5">
                            <span class="text-neutral-300 group-hover:text-sky-400 transition-colors">{{ $cap->capitulo }}. {{ $label }}</span>
                            <span class="font-mono text-sky-400">{{ number_format($cap->total, 0, ',', '.') }}&euro;</span>
                        </div>
                        <div class="h-1.5 bg-neutral-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-500 to-blue-500 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Results -->
    <div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity">
        <div class="space-y-3">
            @forelse ($partidas as $partida)
                <a href="{{ route('presupuestos.entidad', $partida->entidad_id) }}" wire:navigate wire:key="p-{{ $partida->id }}"
                    class="group block p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl hover:bg-neutral-800/60 hover:border-neutral-600/50 transition-all duration-300">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-light text-neutral-200 group-hover:text-white transition-colors mb-1">
                                {{ $partida->entidad->nombre ?? 'Sin entidad' }}
                            </p>
                            <div class="flex flex-wrap gap-2 text-xs text-neutral-400">
                                @if($partida->codigo_economica)
                                    <span class="font-mono">Eco: {{ $partida->codigo_economica }}</span>
                                @endif
                                @if($partida->codigo_funcional)
                                    <span class="font-mono">Fun: {{ $partida->codigo_funcional }}</span>
                                @endif
                                @if($partida->codigo_organica)
                                    <span class="font-mono">Org: {{ $partida->codigo_organica }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="md:text-right shrink-0">
                            <p class="font-mono text-sky-400 text-sm">
                                {{ number_format($partida->credito_actual ?? $partida->credito_definitivo ?? $partida->credito_inicial ?? 0, 0, ',', '.') }}&euro;
                            </p>
                            <p class="text-xs text-neutral-400 mt-1">{{ $partida->ejercicio }}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="px-2 py-1 text-xs rounded-full {{ $partida->tipo_presupuesto === 'gastos' ? 'bg-sky-500/10 text-sky-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                            {{ ucfirst($partida->tipo_presupuesto) }}
                        </span>
                        @if($partida->fuente)
                            <span class="px-2 py-1 text-xs rounded-full bg-neutral-700/30 text-neutral-400">
                                {{ \Modules\Presupuestos\Models\PartidaPresupuestaria::FUENTE_LABELS[$partida->fuente] ?? $partida->fuente }}
                            </span>
                        @endif
                        @if($partida->entidad && $partida->entidad->tipo)
                            <span class="px-2 py-1 text-xs rounded-full bg-neutral-700/30 text-neutral-400">
                                {{ \Modules\Presupuestos\Models\EntidadPresupuestaria::TIPO_LABELS[$partida->entidad->tipo] ?? $partida->entidad->tipo }}
                            </span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
                    <p class="text-neutral-400">No se encontraron partidas con los filtros seleccionados.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            {{ $partidas->links() }}
        </div>
    </div>
</div>
