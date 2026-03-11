<div>
    @section('meta_title', 'Ejecución Presupuestaria - I-Licitaciones')

    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-blue-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <a href="{{ route('presupuestos.index') }}" wire:navigate
               class="inline-flex items-center gap-2 text-neutral-400 hover:text-neutral-200 transition-colors mb-6 group">
                <span class="group-hover:-translate-x-1 transition-transform">&larr;</span>
                <span class="text-sm">Volver a presupuestos</span>
            </a>

            <h1 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Ejecuci&oacute;n Presupuestaria
            </h1>
            <p class="text-neutral-400 mb-6">Aprobado vs obligaciones vs pagos en el tiempo</p>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label for="ej-entidad" class="block text-xs text-neutral-400 mb-2">Entidad</label>
                    <select id="ej-entidad" wire:model.live="entidadId"
                            class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach($entidades as $ent)
                            <option value="{{ $ent->id }}">{{ Str::limit($ent->nombre, 40) }} ({{ $ent->tipo }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="ej-ejercicio" class="block text-xs text-neutral-400 mb-2">Ejercicio</label>
                    <select id="ej-ejercicio" wire:model.live="ejercicio"
                            class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach($ejercicios as $ej)
                            <option value="{{ $ej }}">{{ $ej }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($entidadId && $ejercicio)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-sky-500/30 transition-all duration-300">
                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Aprobado</p>
                <p class="text-xl font-mono text-sky-400">{{ number_format($totalAprobado, 0, ',', '.') }}&euro;</p>
            </div>
            <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-blue-500/30 transition-all duration-300">
                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Obligaciones</p>
                <p class="text-xl font-mono text-blue-400">{{ number_format($totalObligaciones, 0, ',', '.') }}&euro;</p>
                @if($totalAprobado > 0)
                    <p class="text-xs text-neutral-400 mt-1">{{ number_format(($totalObligaciones / $totalAprobado) * 100, 1) }}% del aprobado</p>
                @endif
            </div>
            <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-indigo-500/30 transition-all duration-300">
                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">Pagos realizados</p>
                <p class="text-xl font-mono text-indigo-400">{{ number_format($totalPagos, 0, ',', '.') }}&euro;</p>
                @if($totalAprobado > 0)
                    <p class="text-xs text-neutral-400 mt-1">{{ number_format(($totalPagos / $totalAprobado) * 100, 1) }}% del aprobado</p>
                @endif
            </div>
        </div>

        <!-- Timeline -->
        @if($timeline->isNotEmpty())
            <div class="space-y-3">
                @php
                    $maxTimeline = max($timeline->max('autorizado') ?: 1, $timeline->max('obligaciones') ?: 1, $timeline->max('pagos') ?: 1);
                @endphp

                @foreach($timeline as $item)
                    <div class="p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl">
                        <p class="text-neutral-200 font-mono text-sm mb-3">{{ $item->periodo }}</p>

                        <div class="space-y-2">
                            @php
                                $pctA = round((($item->autorizado ?? 0) / $maxTimeline) * 100);
                                $pctO = round((($item->obligaciones ?? 0) / $maxTimeline) * 100);
                                $pctP = round((($item->pagos ?? 0) / $maxTimeline) * 100);
                            @endphp

                            <div class="flex items-center gap-3">
                                <span class="text-xs text-neutral-400 w-24">Autorizado</span>
                                <div class="flex-1 h-2 bg-neutral-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-sky-500 rounded-full" style="width: {{ $pctA }}%"></div>
                                </div>
                                <span class="font-mono text-xs text-sky-400 w-28 text-right">{{ number_format($item->autorizado ?? 0, 0, ',', '.') }}&euro;</span>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-xs text-neutral-400 w-24">Obligaciones</span>
                                <div class="flex-1 h-2 bg-neutral-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full" style="width: {{ $pctO }}%"></div>
                                </div>
                                <span class="font-mono text-xs text-blue-400 w-28 text-right">{{ number_format($item->obligaciones ?? 0, 0, ',', '.') }}&euro;</span>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-xs text-neutral-400 w-24">Pagos</span>
                                <div class="flex-1 h-2 bg-neutral-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $pctP }}%"></div>
                                </div>
                                <span class="font-mono text-xs text-indigo-400 w-28 text-right">{{ number_format($item->pagos ?? 0, 0, ',', '.') }}&euro;</span>
                            </div>
                        </div>

                        @if($item->pct_ejecucion)
                            <p class="text-xs text-neutral-400 mt-2">Ejecuci&oacute;n: {{ number_format($item->pct_ejecucion, 1) }}%</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
                <p class="text-neutral-400">No hay datos de ejecuci&oacute;n disponibles para esta entidad y ejercicio.</p>
                <p class="text-neutral-500 text-sm mt-2">Los datos de ejecuci&oacute;n se importan con <code class="text-sky-400">budgets:sync-pge</code>.</p>
            </div>
        @endif
    @else
        <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
            <p class="text-neutral-400">Selecciona una entidad y un ejercicio para ver la ejecuci&oacute;n presupuestaria.</p>
        </div>
    @endif
</div>
