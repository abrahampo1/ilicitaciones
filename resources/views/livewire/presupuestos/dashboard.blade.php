<div>
    @section('meta_title', 'Presupuestos Públicos - I-Licitaciones')
    @section('meta_description', 'Panel de presupuestos públicos españoles. Analiza presupuestos del Estado, CCAA y municipios en tiempo real.')

    <!-- Hero Stats Section -->
    <div class="relative mb-12">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-blue-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <p class="text-neutral-400 text-xs md:text-sm mb-2">Datos actualizados</p>
            <h1 class="text-3xl md:text-5xl font-light mb-8 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Presupuestos P&uacute;blicos
            </h1>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-sky-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Presupuestado (Gastos)</p>
                    <p class="text-xl md:text-2xl font-mono text-sky-400">
                        {{ number_format($stats['totalPresupuestado'] ?? 0, 0, ',', '.') }}&euro;</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-blue-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Entidades</p>
                    <p class="text-2xl md:text-3xl font-mono text-blue-400">
                        {{ number_format($stats['totalEntidades'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-indigo-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Partidas</p>
                    <p class="text-2xl md:text-3xl font-mono text-indigo-400">
                        {{ number_format($stats['totalPartidas'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-sky-500/30 transition-all duration-300">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Ejercicios</p>
                    <p class="text-2xl md:text-3xl font-mono text-sky-400">
                        {{ $stats['ejerciciosCount'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Quick links -->
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('presupuestos.explorador') }}" wire:navigate
                    class="px-4 py-2 bg-sky-500/10 border border-sky-500/20 rounded-xl text-sky-400 hover:bg-sky-500/20 hover:border-sky-500/30 transition-all text-sm">
                    Explorar presupuestos &rarr;
                </a>
                <a href="{{ route('presupuestos.comparador') }}" wire:navigate
                    class="px-4 py-2 bg-blue-500/10 border border-blue-500/20 rounded-xl text-blue-400 hover:bg-blue-500/20 hover:border-blue-500/30 transition-all text-sm">
                    Comparador municipal &rarr;
                </a>
                <a href="{{ route('presupuestos.ejecucion') }}" wire:navigate
                    class="px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-400 hover:bg-indigo-500/20 hover:border-indigo-500/30 transition-all text-sm">
                    Ejecuci&oacute;n presupuestaria &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Top Entidades por volumen -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-sky-600/20 to-blue-600/20 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-light">
                        <span class="text-sky-400">&#x2B25;</span> Top 10 Entidades
                    </h2>
                    <a href="{{ route('presupuestos.explorador') }}" wire:navigate
                        class="text-xs text-neutral-400 hover:text-sky-400 transition-colors">
                        Ver todas &rarr;
                    </a>
                </div>

                <div class="space-y-1">
                    @forelse ($topEntidades as $index => $item)
                        <a href="{{ route('presupuestos.entidad', $item->entidad_id) }}" wire:navigate
                            class="group flex items-center py-3 px-3 -mx-3 rounded-xl hover:bg-neutral-800/50 transition-all duration-200">
                            <span class="w-6 text-neutral-400 text-sm font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="flex-1 font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ Str::limit($item->entidad->nombre ?? 'N/A', 35) }}
                            </span>
                            <span class="font-mono text-sm text-sky-400/80 group-hover:text-sky-400 transition-colors">
                                {{ number_format($item->total, 0, ',', '.') }}&euro;
                            </span>
                        </a>
                    @empty
                        <p class="text-neutral-500 text-sm py-4 text-center">Sin datos a&uacute;n. Ejecuta <code class="text-sky-400">budgets:sync-pge</code> para empezar.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Resumen por capítulo -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600/20 to-indigo-600/20 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-light">
                        <span class="text-blue-400">&#x2B25;</span> Gastos por Cap&iacute;tulo
                    </h2>
                </div>

                @php
                    $maxCapitulo = $porCapitulo->max('total') ?: 1;
                    $capLabels = \Modules\Presupuestos\Models\ClasificacionPresupuestaria::CAPITULOS_GASTOS;
                @endphp

                <div class="space-y-3">
                    @forelse ($porCapitulo as $cap)
                        @php
                            $pct = round(($cap->total / $maxCapitulo) * 100);
                            $label = $capLabels[$cap->capitulo] ?? "Cap&iacute;tulo {$cap->capitulo}";
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-neutral-300">{{ $cap->capitulo }}. {{ $label }}</span>
                                <span class="font-mono text-blue-400 text-xs">{{ number_format($cap->total, 0, ',', '.') }}&euro;</span>
                            </div>
                            <div class="h-2 bg-neutral-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-sky-500 to-blue-500 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-neutral-500 text-sm py-4 text-center">Sin datos de cap&iacute;tulos disponibles.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Ejercicios disponibles -->
    @if($ejercicios->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-xl font-light mb-4">
                <span class="text-neutral-400">&#x25C8;</span> Ejercicios Disponibles
            </h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($ejercicios as $ej)
                    <a href="{{ route('presupuestos.explorador', ['ejercicio' => $ej]) }}" wire:navigate
                        class="px-4 py-2 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-neutral-300 hover:text-sky-400 hover:border-sky-500/30 hover:bg-neutral-800/60 transition-all text-sm font-mono">
                        {{ $ej }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
