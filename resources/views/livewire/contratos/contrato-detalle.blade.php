<div>
    @php
        $datos = is_string($licitacion->datos_raiz) ? json_decode($licitacion->datos_raiz, true) : $licitacion->datos_raiz;
        $statusCode = $licitacion->status_code;
        if (!$statusCode && $datos) {
            $rawStatus = $datos['ContractFolderStatus']['ContractFolderStatusCode'] ?? null;
            $statusCode = is_array($rawStatus) ? ($rawStatus['value'] ?? $rawStatus['#text'] ?? $rawStatus[0] ?? null) : $rawStatus;
        }
    @endphp

    @section('meta_title', Str::limit($licitacion->titulo, 60) . ' - I-Licitaciones')
    @section('meta_description', Str::limit(strip_tags($licitacion->descripcion ?? 'Detalles de la licitación ' . $licitacion->titulo), 155))

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left Column -->
        <div class="flex-1 min-w-0">
            <!-- Back navigation -->
            <a href="{{ route('contratos.index') }}" wire:navigate
               class="inline-flex items-center gap-2 text-neutral-400 hover:text-neutral-200 transition-colors mb-6 group">
                <span class="group-hover:-translate-x-1 transition-transform">&larr;</span>
                <span class="text-sm">Volver a contratos</span>
            </a>

            <!-- Title -->
            <div class="relative mb-8">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl pointer-events-none"></div>
                <div class="relative">
                    <h1 class="text-2xl md:text-3xl font-light leading-tight mb-4 text-neutral-100">
                        {{ $licitacion->titulo }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-3">
                        @if($statusCode)
                            @php
                                $estadoClass = match ($statusCode) {
                                    'ADJ', 'RES' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'EV' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    'PUB' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                                    'ANUL' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                    'PRE' => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
                                    default => 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20',
                                };
                            @endphp
                            <span class="px-3 py-1 text-sm rounded-full border {{ $estadoClass }}">
                                {{ \Modules\Contratos\Models\Licitacion::STATUS_LABELS[$statusCode] ?? $licitacion->estado ?? $statusCode }}
                            </span>
                        @elseif($licitacion->estado)
                            <span class="px-3 py-1 text-sm rounded-full bg-neutral-500/10 text-neutral-400 border border-neutral-500/20">
                                {{ $licitacion->estado }}
                            </span>
                        @endif
                        @if($licitacion->expediente)
                            <span class="font-mono text-neutral-400 text-sm">{{ $licitacion->expediente }}</span>
                        @elseif($licitacion->identificador)
                            <span class="font-mono text-neutral-400 text-sm">{{ $licitacion->identificador }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                @if($licitacion->organismo)
                    <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
                        <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-3 font-semibold">Órgano de Contratación</h3>
                        <a href="{{ route('organismos.show', $licitacion->organismo_id) }}" wire:navigate
                           class="text-cyan-400 hover:text-cyan-300 transition-colors">
                            {{ $licitacion->organismo->nombre }}
                        </a>
                        @if($licitacion->organismo->provincia)
                            <p class="text-xs text-neutral-400 mt-1">{{ $licitacion->organismo->provincia }}</p>
                        @endif
                    </div>
                @endif

                @if($licitacion->tipo_contrato_code || $licitacion->procedimiento_code)
                    <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
                        <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-3 font-semibold">Tipo y Procedimiento</h3>
                        @if($licitacion->tipo_contrato_code)
                            <p class="text-neutral-200">{{ \Modules\Contratos\Models\Licitacion::TIPO_LABELS[$licitacion->tipo_contrato_code] ?? 'Otro' }}</p>
                        @endif
                        @if($licitacion->procedimiento_code)
                            <p class="text-neutral-400 text-sm mt-1">{{ \Modules\Contratos\Models\Licitacion::PROCEDIMIENTO_LABELS[$licitacion->procedimiento_code] ?? 'Otro' }}</p>
                        @endif
                        @if($licitacion->urgencia_code)
                            <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full bg-amber-500/10 text-amber-400">Urgencia: {{ $licitacion->urgencia_code }}</span>
                        @endif
                    </div>
                @endif

                @if($licitacion->comunidad_autonoma || $licitacion->lugar_ejecucion)
                    <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
                        <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-3 font-semibold">Lugar de Ejecución</h3>
                        @if($licitacion->lugar_ejecucion)
                            <p class="text-neutral-200">{{ $licitacion->lugar_ejecucion }}</p>
                        @endif
                        @if($licitacion->comunidad_autonoma)
                            <p class="text-neutral-400 text-sm mt-1">{{ $licitacion->comunidad_autonoma }}</p>
                        @endif
                    </div>
                @endif

                @if($licitacion->adjudicatario_nombre)
                    <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
                        <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-3 font-semibold">Adjudicatario</h3>
                        <p class="text-emerald-400">{{ $licitacion->adjudicatario_nombre }}</p>
                        @if($licitacion->adjudicatario_nif)
                            <p class="text-xs text-neutral-400 font-mono mt-1">NIF: {{ $licitacion->adjudicatario_nif }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Dates -->
            @if($licitacion->fecha_presentacion_limite || $licitacion->fecha_adjudicacion || $licitacion->fecha_formalizacion || $licitacion->fecha_inicio || $licitacion->fecha_fin)
                <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl mb-8">
                    <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-4 font-semibold">Fechas</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        @if($licitacion->fecha_presentacion_limite)
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Límite presentación</p>
                                <p class="text-neutral-200 font-mono">{{ $licitacion->fecha_presentacion_limite->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($licitacion->fecha_adjudicacion)
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Adjudicación</p>
                                <p class="text-neutral-200 font-mono">{{ $licitacion->fecha_adjudicacion->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($licitacion->fecha_formalizacion)
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Formalización</p>
                                <p class="text-neutral-200 font-mono">{{ $licitacion->fecha_formalizacion->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($licitacion->fecha_inicio)
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Inicio</p>
                                <p class="text-neutral-200 font-mono">{{ $licitacion->fecha_inicio->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($licitacion->fecha_fin)
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Fin</p>
                                <p class="text-neutral-200 font-mono">{{ $licitacion->fecha_fin->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($licitacion->duracion)
                            <div>
                                <p class="text-neutral-400 text-xs mb-1">Duración</p>
                                <p class="text-neutral-200 font-mono">{{ $licitacion->duracion }} {{ $licitacion->duracion_unidad ?? 'meses' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Description -->
            @if($licitacion->descripcion)
                <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl mb-8">
                    <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-4 font-semibold">Descripción</h3>
                    <div class="text-neutral-300 text-sm leading-relaxed prose prose-invert max-w-none">
                        {!! nl2br(e($licitacion->descripcion)) !!}
                    </div>
                </div>
            @endif

            <!-- Link externo -->
            @if($licitacion->link || $licitacion->url)
                <div class="mb-8">
                    <a href="{{ $licitacion->link ?? $licitacion->url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-cyan-400 hover:text-cyan-300 hover:border-cyan-500/30 transition-all text-sm">
                        Ver en la plataforma oficial <span>&nearr;</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Right Column - Stats -->
        <div class="lg:w-80 space-y-6">
            <!-- Importes -->
            <div class="space-y-4">
                @if($licitacion->importe_con_iva ?? $licitacion->importe_total)
                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Presupuesto (IVA inc.)</p>
                        <p class="text-3xl font-mono text-emerald-400 font-light tracking-tight">
                            {{ number_format($licitacion->importe_con_iva ?? $licitacion->importe_total ?? 0, 0, ',', '.') }}&euro;
                        </p>
                    </div>
                @endif

                @if($licitacion->importe_sin_iva ?? $licitacion->importe_final)
                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-teal-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Presupuesto (sin IVA)</p>
                        <p class="text-2xl font-mono text-teal-400 font-light tracking-tight">
                            {{ number_format($licitacion->importe_sin_iva ?? $licitacion->importe_final ?? 0, 0, ',', '.') }}&euro;
                        </p>
                    </div>
                @endif

                @if($licitacion->importe_adjudicacion_con_iva)
                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Importe Adjudicación</p>
                        <p class="text-2xl font-mono text-cyan-400 font-light tracking-tight">
                            {{ number_format($licitacion->importe_adjudicacion_con_iva, 0, ',', '.') }}&euro;
                        </p>
                    </div>
                @endif

                @if($licitacion->valor_estimado ?? $licitacion->importe_estimado)
                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-sky-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Valor Estimado</p>
                        <p class="text-2xl font-mono text-sky-400 font-light tracking-tight">
                            {{ number_format($licitacion->valor_estimado ?? $licitacion->importe_estimado ?? 0, 0, ',', '.') }}&euro;
                        </p>
                    </div>
                @endif
            </div>

            <!-- Extra info -->
            @if($licitacion->num_ofertas || $licitacion->cpv_codes)
                <div class="bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6">
                    <h3 class="text-neutral-400 text-xs uppercase tracking-wider mb-4 font-semibold">Información adicional</h3>
                    @if($licitacion->num_ofertas)
                        <div class="mb-3">
                            <p class="text-neutral-400 text-xs">Ofertas recibidas</p>
                            <p class="text-neutral-200 font-mono text-lg">{{ $licitacion->num_ofertas }}</p>
                        </div>
                    @endif
                    @if($licitacion->cpv_codes)
                        <div>
                            <p class="text-neutral-400 text-xs mb-2">Códigos CPV</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($licitacion->cpv_codes as $cpv)
                                    <span class="px-2 py-0.5 text-xs font-mono bg-neutral-800 rounded-lg text-neutral-300">{{ $cpv }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
