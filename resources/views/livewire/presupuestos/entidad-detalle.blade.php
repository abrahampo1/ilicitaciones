<div>
    @section('meta_title', $entidad->nombre . ' - Presupuestos - I-Licitaciones')
    @section('meta_description', 'Presupuesto de ' . $entidad->nombre . '. Desglose por capítulos, evolución y ejecución presupuestaria.')

    <!-- Back navigation -->
    <a href="{{ route('presupuestos.explorador') }}" wire:navigate
       class="inline-flex items-center gap-2 text-neutral-400 hover:text-neutral-200 transition-colors mb-6 group">
        <span class="group-hover:-translate-x-1 transition-transform">&larr;</span>
        <span class="text-sm">Volver al explorador</span>
    </a>

    <!-- Title -->
    <div class="relative mb-8">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-blue-500/5 to-transparent rounded-3xl blur-3xl pointer-events-none"></div>
        <div class="relative">
            <h1 class="text-2xl md:text-3xl font-light leading-tight mb-4 text-neutral-100">
                {{ $entidad->nombre }}
            </h1>
            <div class="flex flex-wrap items-center gap-3">
                <span class="px-3 py-1 text-sm rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20">
                    {{ $entidad->tipo_label }}
                </span>
                @if($entidad->codigo_ine)
                    <span class="font-mono text-neutral-400 text-sm">INE: {{ $entidad->codigo_ine }}</span>
                @endif
                @if($entidad->poblacion)
                    <span class="text-neutral-400 text-sm">{{ number_format($entidad->poblacion, 0, ',', '.') }} hab.</span>
                @endif
                @if($entidad->provincia)
                    <span class="text-neutral-400 text-sm">{{ $entidad->provincia }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Ejercicio selector + Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex gap-2">
            <button wire:click="$set('tab', 'gastos')"
                    class="px-4 py-2 rounded-xl text-sm transition-all {{ $tab === 'gastos' ? 'bg-sky-500/10 text-sky-400 border border-sky-500/30' : 'text-neutral-400 hover:text-neutral-200 border border-neutral-700/50 hover:border-neutral-600' }}">
                Gastos
            </button>
            <button wire:click="$set('tab', 'ingresos')"
                    class="px-4 py-2 rounded-xl text-sm transition-all {{ $tab === 'ingresos' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30' : 'text-neutral-400 hover:text-neutral-200 border border-neutral-700/50 hover:border-neutral-600' }}">
                Ingresos
            </button>
            <button wire:click="$set('tab', 'ejecucion')"
                    class="px-4 py-2 rounded-xl text-sm transition-all {{ $tab === 'ejecucion' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/30' : 'text-neutral-400 hover:text-neutral-200 border border-neutral-700/50 hover:border-neutral-600' }}">
                Ejecuci&oacute;n
            </button>
        </div>

        <div>
            <select wire:model.live="ejercicio"
                    class="bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                @foreach($ejercicios as $ej)
                    <option value="{{ $ej }}">{{ $ej }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Total -->
    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden mb-8">
        <div class="absolute inset-0 bg-gradient-to-br from-sky-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-1">Total {{ ucfirst($tab === 'ejecucion' ? 'gastos' : $tab) }} {{ $ejercicio }}</p>
                <p class="text-3xl font-mono text-sky-400 font-light tracking-tight">
                    {{ number_format($totalPresupuesto, 0, ',', '.') }}&euro;
                </p>
            </div>
            @if($entidad->poblacion && $totalPresupuesto > 0)
                <div class="text-right">
                    <p class="text-neutral-400 text-xs uppercase tracking-wider mb-1">Per c&aacute;pita</p>
                    <p class="text-xl font-mono text-blue-400 font-light">
                        {{ number_format($totalPresupuesto / $entidad->poblacion, 2, ',', '.') }}&euro;
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if($tab !== 'ejecucion')
        <!-- Desglose por capítulo -->
        <div class="space-y-3">
            @php $maxCap = $porCapitulo->max('total') ?: 1; @endphp
            @forelse($porCapitulo as $cap)
                @php
                    $pct = round(($cap->total / $maxCap) * 100);
                    $label = $capituloLabels[$cap->capitulo] ?? "Cap&iacute;tulo {$cap->capitulo}";
                    $colorClass = match($cap->capitulo) {
                        '1', '2' => 'from-sky-500 to-sky-400',
                        '3', '4', '5' => 'from-blue-500 to-blue-400',
                        '6', '7' => 'from-indigo-500 to-indigo-400',
                        default => 'from-neutral-500 to-neutral-400',
                    };
                @endphp
                <div class="p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl hover:bg-neutral-800/60 transition-all">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="text-neutral-200 font-light">{{ $cap->capitulo }}. {{ $label }}</p>
                            @if($totalPresupuesto > 0)
                                <p class="text-xs text-neutral-400 mt-1">{{ number_format(($cap->total / $totalPresupuesto) * 100, 1) }}% del total</p>
                            @endif
                        </div>
                        <p class="font-mono text-sky-400 text-sm">{{ number_format($cap->total, 0, ',', '.') }}&euro;</p>
                    </div>
                    <div class="h-2 bg-neutral-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r {{ $colorClass }} rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
                    <p class="text-neutral-400">No hay datos para este ejercicio.</p>
                </div>
            @endforelse
        </div>
    @else
        <!-- Ejecución timeline -->
        @if(count($ejecucion) > 0)
            <div class="space-y-3">
                @foreach($ejecucion as $ej)
                    <div class="p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl">
                        <div class="flex justify-between items-start mb-3">
                            <p class="text-neutral-200 font-mono">{{ $ej->periodo }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Autorizado</p>
                                <p class="font-mono text-sky-400">{{ number_format($ej->autorizado ?? 0, 0, ',', '.') }}&euro;</p>
                            </div>
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Obligaciones</p>
                                <p class="font-mono text-blue-400">{{ number_format($ej->obligaciones ?? 0, 0, ',', '.') }}&euro;</p>
                            </div>
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Pagos</p>
                                <p class="font-mono text-indigo-400">{{ number_format($ej->pagos ?? 0, 0, ',', '.') }}&euro;</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
                <p class="text-neutral-400">No hay datos de ejecuci&oacute;n para este ejercicio.</p>
            </div>
        @endif
    @endif

    <!-- Cross-link con Contratos -->
    @if($organismoContratos)
        <div class="mt-8 p-6 bg-neutral-900/50 border border-emerald-500/20 rounded-2xl">
            <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-3">Enlace con Contratos</h3>
            <a href="{{ route('organismos.show', $organismoContratos->id) }}" wire:navigate
               class="text-emerald-400 hover:text-emerald-300 transition-colors">
                Ver contratos de {{ $organismoContratos->nombre }} &rarr;
            </a>
        </div>
    @endif
</div>
