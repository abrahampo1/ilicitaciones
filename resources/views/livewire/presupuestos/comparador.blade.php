<div>
    @section('meta_title', 'Comparador Municipal - Presupuestos - I-Licitaciones')

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
                Comparador Municipal
            </h1>
            <p class="text-neutral-400 mb-6">Compara presupuestos per c&aacute;pita entre dos municipios</p>

            <!-- Selectors -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl">
                <div>
                    <label for="mun1" class="block text-xs text-neutral-400 mb-2">Municipio 1</label>
                    <select id="mun1" wire:model.live="municipio1"
                            class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach($municipios as $mun)
                            <option value="{{ $mun->id }}">{{ $mun->nombre }} ({{ $mun->codigo_ine }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="mun2" class="block text-xs text-neutral-400 mb-2">Municipio 2</label>
                    <select id="mun2" wire:model.live="municipio2"
                            class="w-full bg-neutral-900 border border-neutral-700/50 rounded-xl text-neutral-200 py-2 px-4 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach($municipios as $mun)
                            <option value="{{ $mun->id }}">{{ $mun->nombre }} ({{ $mun->codigo_ine }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="comp-ej" class="block text-xs text-neutral-400 mb-2">Ejercicio</label>
                    <select id="comp-ej" wire:model.live="ejercicio"
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

    <!-- Comparison Results -->
    @if($comparacion && count($comparacion) === 2)
        @php
            $ids = array_keys($comparacion);
            $data1 = $comparacion[$ids[0]];
            $data2 = $comparacion[$ids[1]];
        @endphp

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach([$data1, $data2] as $i => $data)
                <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br {{ $i === 0 ? 'from-sky-500/10' : 'from-blue-500/10' }} to-transparent opacity-50"></div>
                    <div class="relative">
                        <h3 class="text-lg font-light text-neutral-100 mb-4">{{ $data['entidad']->nombre }}</h3>
                        <p class="text-xs text-neutral-400 mb-1">{{ number_format($data['entidad']->poblacion ?? 0, 0, ',', '.') }} habitantes</p>

                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-1">Gastos total</p>
                                <p class="font-mono text-sky-400">{{ number_format($data['gastos_total'], 0, ',', '.') }}&euro;</p>
                            </div>
                            <div>
                                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-1">Gastos/hab</p>
                                <p class="font-mono {{ $i === 0 ? 'text-sky-400' : 'text-blue-400' }} text-xl">{{ number_format($data['gastos_per_capita'], 2, ',', '.') }}&euro;</p>
                            </div>
                            <div>
                                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-1">Ingresos total</p>
                                <p class="font-mono text-indigo-400">{{ number_format($data['ingresos_total'], 0, ',', '.') }}&euro;</p>
                            </div>
                            <div>
                                <p class="text-neutral-400 text-xs uppercase tracking-wider mb-1">Ingresos/hab</p>
                                <p class="font-mono text-indigo-400">{{ number_format($data['ingresos_per_capita'], 2, ',', '.') }}&euro;</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Side by side chapter comparison -->
        <div class="bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6">
            <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-6">Comparaci&oacute;n por cap&iacute;tulo de gastos</h3>
            @php
                $caps1 = collect($data1['capitulos'])->keyBy('capitulo');
                $caps2 = collect($data2['capitulos'])->keyBy('capitulo');
                $allCaps = $caps1->keys()->merge($caps2->keys())->unique()->sort();
                $maxVal = max($caps1->max('total') ?: 1, $caps2->max('total') ?: 1);
            @endphp

            <div class="space-y-4">
                @foreach($allCaps as $cap)
                    @php
                        $v1 = $caps1[$cap]->total ?? 0;
                        $v2 = $caps2[$cap]->total ?? 0;
                        $pct1 = round(($v1 / $maxVal) * 100);
                        $pct2 = round(($v2 / $maxVal) * 100);
                        $label = $capituloLabels[$cap] ?? "Cap. {$cap}";
                    @endphp
                    <div>
                        <p class="text-neutral-300 text-sm mb-2">{{ $cap }}. {{ $label }}</p>
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-neutral-400 w-24 truncate">{{ Str::limit($data1['entidad']->nombre, 12) }}</span>
                                <div class="flex-1 h-2 bg-neutral-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-sky-500 rounded-full" style="width: {{ $pct1 }}%"></div>
                                </div>
                                <span class="font-mono text-xs text-sky-400 w-24 text-right">{{ number_format($v1, 0, ',', '.') }}&euro;</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-neutral-400 w-24 truncate">{{ Str::limit($data2['entidad']->nombre, 12) }}</span>
                                <div class="flex-1 h-2 bg-neutral-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full" style="width: {{ $pct2 }}%"></div>
                                </div>
                                <span class="font-mono text-xs text-blue-400 w-24 text-right">{{ number_format($v2, 0, ',', '.') }}&euro;</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($municipio1 || $municipio2)
        <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
            <p class="text-neutral-400">Selecciona dos municipios y un ejercicio para comparar.</p>
        </div>
    @else
        <div class="p-12 bg-neutral-800/30 border border-neutral-700/30 rounded-xl text-center">
            <p class="text-neutral-400">Selecciona dos municipios y un ejercicio para empezar la comparaci&oacute;n.</p>
        </div>
    @endif
</div>
