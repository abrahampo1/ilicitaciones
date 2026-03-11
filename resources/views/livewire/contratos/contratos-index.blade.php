<div>
    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h1 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Contratos
            </h1>
            <p class="text-neutral-400 mb-6">Licitaciones y contratos del sector público</p>

            <!-- Search Bar -->
            <div class="max-w-4xl">
                <div class="relative group mb-4">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl opacity-20 group-hover:opacity-40 transition duration-200 blur"></div>
                    <div class="relative flex items-center bg-neutral-900 rounded-xl">
                        <label for="search-contratos" class="pl-4 text-neutral-400">
                            <span class="sr-only">Buscar contratos</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </label>
                        <input type="text"
                               id="search-contratos"
                               wire:model.live.debounce.300ms="search"
                               placeholder="Buscar por título, expediente, adjudicatario o NIF..."
                               class="w-full bg-transparent border-none focus:ring-0 text-neutral-200 placeholder-neutral-400 py-3 pl-3 pr-4">
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <!-- Status -->
                    <div>
                        <label for="filter-status" class="block text-xs text-neutral-400 mb-2">Estado</label>
                        <select id="filter-status" wire:model.live="status"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                            <option value="">Todos</option>
                            @foreach($statusLabels as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label for="filter-tipo" class="block text-xs text-neutral-400 mb-2">Tipo</label>
                        <select id="filter-tipo" wire:model.live="tipo"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                            <option value="">Todos</option>
                            @foreach($tipoLabels as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Procedimiento -->
                    <div>
                        <label for="filter-proc" class="block text-xs text-neutral-400 mb-2">Procedimiento</label>
                        <select id="filter-proc" wire:model.live="procedimiento"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                            <option value="">Todos</option>
                            @foreach($procedimientoLabels as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- CCAA -->
                    <div>
                        <label for="filter-ccaa" class="block text-xs text-neutral-400 mb-2">Comunidad</label>
                        <select id="filter-ccaa" wire:model.live="ccaa"
                                class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                            <option value="">Todas</option>
                            @foreach($comunidades as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Importe Min -->
                    <div>
                        <label for="filter-min" class="block text-xs text-neutral-400 mb-2">Importe mín.</label>
                        <input type="number" id="filter-min" wire:model.live.debounce.500ms="importeMin"
                               placeholder="0" step="1000"
                               class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 placeholder-neutral-400 py-2 px-4 focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    </div>

                    <!-- Importe Max -->
                    <div>
                        <label for="filter-max" class="block text-xs text-neutral-400 mb-2">Importe máx.</label>
                        <input type="number" id="filter-max" wire:model.live.debounce.500ms="importeMax"
                               placeholder="Sin límite" step="1000"
                               class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 placeholder-neutral-400 py-2 px-4 focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    </div>
                </div>

                @if($search || $status || $tipo || $procedimiento || $ccaa || $importeMin || $importeMax)
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

    <!-- Results -->
    <div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity">
        <!-- Sort controls -->
        <div class="flex flex-wrap items-center gap-3 mb-6 text-xs text-neutral-400">
            <span>Ordenar por:</span>
            @foreach(['fecha_actualizacion' => 'Fecha', 'importe_total' => 'Importe', 'expediente' => 'Expediente'] as $field => $label)
                <button wire:click="sortBy('{{ $field }}')"
                        class="px-3 py-1 rounded-full border transition-colors
                            {{ $sort === $field ? 'border-emerald-500/50 text-emerald-400 bg-emerald-500/10' : 'border-neutral-700/50 hover:border-neutral-600' }}">
                    {{ $label }}
                    @if($sort === $field)
                        <span>{{ $dir === 'asc' ? '&uarr;' : '&darr;' }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="space-y-3">
            @forelse ($licitaciones as $lic)
                <a href="{{ route('contratos.show', $lic->id) }}" wire:navigate wire:key="lic-{{ $lic->id }}"
                    class="group block p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl hover:bg-neutral-800/60 hover:border-neutral-600/50 transition-all duration-300">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-light text-neutral-200 group-hover:text-white transition-colors line-clamp-2 mb-2">
                                {{ $lic->titulo }}
                            </p>
                            <p class="text-xs text-neutral-400 truncate">
                                {{ $lic->organismo->nombre ?? 'Sin organismo' }}
                                @if($lic->expediente)
                                    <span class="ml-2 font-mono">{{ Str::limit($lic->expediente, 30) }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="md:text-right shrink-0">
                            <p class="font-mono text-emerald-400 text-sm">
                                {{ number_format($lic->importe_total ?? $lic->importe_con_iva ?? 0, 0, ',', '.') }}&euro;
                            </p>
                            <p class="text-xs text-neutral-400 mt-1">
                                {{ $lic->fecha_actualizacion ? \Carbon\Carbon::parse($lic->fecha_actualizacion)->format('d/m/Y') : '' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @php
                            $statusCode = $lic->status_code;
                            $estadoLabel = $statusCode ? (\Modules\Contratos\Models\Licitacion::STATUS_LABELS[$statusCode] ?? $lic->estado) : $lic->estado;
                            $estadoClass = match ($statusCode) {
                                'ADJ', 'RES' => 'bg-emerald-500/10 text-emerald-400',
                                'EV' => 'bg-amber-500/10 text-amber-400',
                                'PUB' => 'bg-sky-500/10 text-sky-400',
                                'ANUL' => 'bg-red-500/10 text-red-400',
                                'PRE' => 'bg-violet-500/10 text-violet-400',
                                default => 'bg-neutral-500/10 text-neutral-400',
                            };
                        @endphp
                        @if($estadoLabel)
                            <span class="px-2 py-1 text-xs rounded-full {{ $estadoClass }}">{{ $estadoLabel }}</span>
                        @endif
                        @if($lic->tipo_contrato_code && isset($tipoLabels[$lic->tipo_contrato_code]))
                            <span class="px-2 py-1 text-xs rounded-full bg-neutral-700/30 text-neutral-400">
                                {{ $tipoLabels[$lic->tipo_contrato_code] }}
                            </span>
                        @endif
                        @if($lic->comunidad_autonoma)
                            <span class="px-2 py-1 text-xs rounded-full bg-neutral-700/30 text-neutral-400">
                                {{ $lic->comunidad_autonoma }}
                            </span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
                    <p class="text-neutral-400">No se encontraron contratos con los filtros seleccionados.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            {{ $licitaciones->links() }}
        </div>
    </div>
</div>
