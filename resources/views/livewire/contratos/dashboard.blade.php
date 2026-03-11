<div>
    @section('meta_title', 'I-Licitaciones | Inteligencia de Mercado Público')
    @section('meta_description', 'Panel de inteligencia de licitaciones. Analiza adjudicaciones, organismos y empresas del sector público en tiempo real.')

    <!-- Hero Stats Section -->
    <div class="relative mb-12">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <p class="text-neutral-400 text-xs md:text-sm mb-2">Hasta el
                {{ $stats['latestDate'] ? \Carbon\Carbon::parse($stats['latestDate'])->format('d/m/Y H:i') : 'N/A' }}</p>
            <h1 class="text-3xl md:text-5xl font-light mb-8 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Panel de Licitaciones
            </h1>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-emerald-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Licitaciones</p>
                    <p class="text-2xl md:text-3xl font-mono text-emerald-400">
                        {{ number_format($stats['conteoLicitaciones'], 0, ',', '.') }}</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-teal-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Volumen Total</p>
                    <p class="text-xl md:text-2xl font-mono text-teal-400">
                        {{ number_format($stats['totalImporte'], 2, ',', '.') }}&euro;</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-cyan-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Organismos</p>
                    <p class="text-2xl md:text-3xl font-mono text-cyan-400">
                        {{ number_format($stats['totalOrganismos'], 0, ',', '.') }}</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-sky-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Empresas</p>
                    <p class="text-2xl md:text-3xl font-mono text-sky-400">
                        {{ number_format($stats['totalEmpresas'], 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Search bar que redirige a /contratos -->
            <div class="max-w-2xl">
                <form action="{{ route('contratos.index') }}" method="GET" class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl opacity-20 group-hover:opacity-40 transition duration-200 blur"></div>
                    <div class="relative flex items-center bg-neutral-900 rounded-xl">
                        <label for="search-home" class="pl-4 text-neutral-400">
                            <span class="sr-only">Buscar contratos</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </label>
                        <input type="text" id="search-home" name="search" placeholder="Buscar licitaciones, organismos, empresas..."
                            class="w-full bg-transparent border-none focus:ring-0 text-neutral-200 placeholder-neutral-400 py-3 pl-3 pr-4">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Top Empresas -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-light">
                        <span class="text-emerald-400">&#x2B25;</span> Top 10 Empresas
                    </h2>
                    <a href="{{ route('empresas.index') }}" wire:navigate
                        class="text-xs text-neutral-400 hover:text-emerald-400 transition-colors">
                        Ver todas &rarr;
                    </a>
                </div>

                <div class="space-y-1">
                    @foreach ($topEmpresas as $index => $adjudicacion)
                        <a href="{{ route('empresas.show', $adjudicacion->empresa_id) }}" wire:navigate
                            class="group flex items-center py-3 px-3 -mx-3 rounded-xl hover:bg-neutral-800/50 transition-all duration-200">
                            <span class="w-6 text-neutral-400 text-sm font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="flex-1 font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ Str::limit($adjudicacion->empresa->nombre ?? 'N/A', 35) }}
                            </span>
                            <span class="font-mono text-sm text-emerald-400/80 group-hover:text-emerald-400 transition-colors">
                                {{ number_format($adjudicacion->total_importe, 0, ',', '.') }}&euro;
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Organismos -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-cyan-600/20 to-sky-600/20 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-light">
                        <span class="text-cyan-400">&#x2B25;</span> Top 10 Organismos
                    </h2>
                    <a href="{{ route('organismos.index') }}" wire:navigate
                        class="text-xs text-neutral-400 hover:text-cyan-400 transition-colors">
                        Ver todos &rarr;
                    </a>
                </div>

                <div class="space-y-1">
                    @foreach ($topOrganismos as $index => $organismo)
                        <a href="{{ route('organismos.show', $organismo->organismo_id) }}" wire:navigate
                            class="group flex items-center py-3 px-3 -mx-3 rounded-xl hover:bg-neutral-800/50 transition-all duration-200">
                            <span class="w-6 text-neutral-400 text-sm font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="flex-1 font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ Str::limit($organismo->organismo->nombre ?? 'N/A', 35) }}
                            </span>
                            <span class="font-mono text-sm text-cyan-400/80 group-hover:text-cyan-400 transition-colors">
                                {{ number_format($organismo->total_importe ?? 0, 0, ',', '.') }}&euro;
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Licitaciones -->
    <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-light">
                <span class="text-neutral-400">&#x25C8;</span> Últimas Licitaciones
            </h2>
            <a href="{{ route('contratos.index') }}" wire:navigate
                class="text-xs text-neutral-400 hover:text-emerald-400 transition-colors">
                Ver todas &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($ultimasLicitaciones as $lic)
                <a href="{{ route('contratos.show', $lic->id) }}" wire:navigate
                    class="group p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl hover:bg-neutral-800/60 hover:border-neutral-600/50 transition-all duration-300">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-light text-neutral-200 group-hover:text-white transition-colors line-clamp-2 mb-2">
                                {{ Str::limit($lic->titulo, 80) }}
                            </p>
                            <p class="text-xs text-neutral-400 truncate">
                                {{ Str::limit($lic->organismo->nombre ?? 'Sin organismo', 40) }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-mono text-emerald-400 text-sm">
                                {{ number_format($lic->importe_total, 0, ',', '.') }}&euro;
                            </p>
                            <p class="text-xs text-neutral-400 mt-1">
                                {{ $lic->fecha_actualizacion ? \Carbon\Carbon::parse($lic->fecha_actualizacion)->format('d/m/Y') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        @php
                            $statusCode = $lic->status_code ?? null;
                            $estadoLabel = $lic->status_code ? (\Modules\Contratos\Models\Licitacion::STATUS_LABELS[$lic->status_code] ?? $lic->estado) : $lic->estado;
                            $estadoClass = match ($statusCode) {
                                'ADJ', 'RES' => 'bg-emerald-500/10 text-emerald-400',
                                'EV' => 'bg-amber-500/10 text-amber-400',
                                'PUB' => 'bg-sky-500/10 text-sky-400',
                                'ANUL' => 'bg-red-500/10 text-red-400',
                                default => match ($lic->estado) {
                                    'Adjudicada' => 'bg-emerald-500/10 text-emerald-400',
                                    'Evaluación' => 'bg-amber-500/10 text-amber-400',
                                    'Publicada' => 'bg-sky-500/10 text-sky-400',
                                    default => 'bg-neutral-500/10 text-neutral-400',
                                },
                            };
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $estadoClass }}">
                            {{ $estadoLabel ?? 'Sin estado' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
