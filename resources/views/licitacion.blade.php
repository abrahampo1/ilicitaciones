@extends('layouts.app')

@section('contenido')
    @section('meta_title', Str::limit($licitacion->titulo, 60) . ' - I-Licitaciones')
    @section('meta_description', Str::limit(strip_tags($licitacion->descripcion ?? 'Detalles de la licitación ' . $licitacion->titulo), 155))

    @push('json-ld')
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "GovernmentService",
      "name": "{{ str_replace('"', '\"', $licitacion->titulo) }}",
      "description": "{{ str_replace('"', '\"', Str::limit(strip_tags($licitacion->descripcion), 150)) }}",
      "provider": {
        "@@type": "GovernmentOrganization",
        "name": "{{ str_replace('"', '\"', $licitacion->organismo->nombre ?? 'Organismo Público') }}"
      },
      "datePublished": "{{ $licitacion->created_at }}",
      "dateModified": "{{ $licitacion->updated_at }}"
    }
    </script>
    @endpush

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="text-xs text-neutral-500 flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:text-neutral-300 transition-colors">Inicio</a>
        <span>/</span>
        <span class="text-neutral-300 truncate max-w-xs">{{ Str::limit($licitacion->titulo, 50) }}</span>
    </nav>
@endsection

    @php
        $statusCode = $parsedData['statusCode'];
    @endphp

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left Column - Licitación Details -->
        <div class="flex-1 min-w-0">
        <!-- Back navigation -->
        <a href="{{ url()->previous() }}"
           class="inline-flex items-center gap-2 text-neutral-400 hover:text-neutral-200 transition-colors mb-6 group">
            <span class="group-hover:-translate-x-1 transition-transform">&larr;</span>
            <span class="text-sm">Volver</span>
        </a>

        <!-- Header Section -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
            <div class="relative">
                <!-- Estado Badge -->
                <div class="mb-4">
                    @php
                        $statusMap = [
                            'PRE' => 'Anuncio Previo',
                            'PUB' => 'Publicada',
                            'EV' => 'En Evaluación',
                            'ADJ' => 'Adjudicada',
                            'RES' => 'Resuelta',
                            'ANUL' => 'Anulada',
                            'DES' => 'Desierta',
                            'S' => 'Suspendida'
                        ];

                        $displayText = (is_string($statusCode) || is_numeric($statusCode)) && isset($statusMap[$statusCode])
                            ? $statusMap[$statusCode]
                            : ($licitacion->estado ?? 'Sin estado');

                        $estadoClass = match($statusCode) {
                            'ADJ', 'RES' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
                            'EV' => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
                            'PUB', 'PRE' => 'bg-sky-500/20 text-sky-400 border border-sky-500/30',
                            'ANUL', 'DES', 'S' => 'bg-red-500/20 text-red-400 border border-red-500/30',
                            default => match($licitacion->estado) {
                                'Adjudicada' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
                                'Evaluación' => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
                                'Publicada' => 'bg-sky-500/20 text-sky-400 border border-sky-500/30',
                                default => 'bg-neutral-500/20 text-neutral-400 border border-neutral-500/30'
                            }
                        };
                    @endphp
                    <span class="px-3 py-1.5 text-xs rounded-full font-medium {{ $estadoClass }}">
                        {{ $displayText }}
                        @if($statusCode && !isset($statusMap[$statusCode]))
                            <span class="ml-1 opacity-75">({{ $statusCode }})</span>
                        @endif
                    </span>
                </div>

                <!-- Título -->
                <h1 class="text-2xl md:text-3xl font-light leading-tight mb-4 text-neutral-100">
                    {{ $licitacion->titulo }}
                </h1>

                <!-- Identificador y Organismo -->
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <span class="font-mono text-neutral-400">{{ $licitacion->identificador }}</span>
                    @if($licitacion->organismo)
                        <span class="text-neutral-700">&bull;</span>
                        <a href="{{ route('organismo.show', $licitacion->organismo->id) }}"
                           class="text-cyan-400 hover:text-cyan-300 transition-colors">
                            {{ Str::limit($licitacion->organismo->nombre, 50) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Importes Grid -->
        @php
            $importes = [
                ['label' => 'Presupuesto Base', 'value' => $licitacion->importe_estimado, 'glow' => 'from-amber-500/20',   'hover' => 'group-hover:border-amber-500/30',   'text' => 'text-amber-400',   'sym' => 'text-amber-500/60'],
                ['label' => 'Sin Impuestos',    'value' => $licitacion->importe_final,    'glow' => 'from-teal-500/20',    'hover' => 'group-hover:border-teal-500/30',    'text' => 'text-teal-400',    'sym' => 'text-teal-500/60'],
                ['label' => 'Importe Total',     'value' => $licitacion->importe_total,    'glow' => 'from-emerald-500/20', 'hover' => 'group-hover:border-emerald-500/30', 'text' => 'text-emerald-400', 'sym' => 'text-emerald-500/60'],
            ];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3 gap-4 mb-8">
            @foreach ($importes as $importe)
                <div class="relative group min-w-0">
                    <div class="absolute inset-0 bg-gradient-to-br {{ $importe['glow'] }} to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl {{ $importe['hover'] }} transition-colors min-w-0">
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">{{ $importe['label'] }}</p>
                        @if ($importe['value'])
                            <p class="font-mono tabular-nums leading-tight break-words {{ $importe['text'] }} text-2xl xl:text-lg">
                                {{ number_format($importe['value'], 2, ',', '.') }}<span class="text-[0.65em] {{ $importe['sym'] }} ml-0.5">&euro;</span>
                            </p>
                        @else
                            <p class="text-2xl font-mono text-neutral-600">&mdash;</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Fechas -->
        @if($licitacion->fecha_contratacion || $licitacion->fecha_actualizacion)
            <div class="flex flex-wrap gap-6 mb-8 text-sm">
                @if($licitacion->fecha_contratacion)
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                        <span class="text-neutral-400">Contratación:</span>
                        <span class="text-neutral-300">{{ Carbon\Carbon::parse($licitacion->fecha_contratacion)->format('d/m/Y') }}</span>
                    </div>
                @endif
                @if($licitacion->fecha_actualizacion)
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-neutral-500"></span>
                        <span class="text-neutral-400">Actualización:</span>
                        <span class="text-neutral-300">{{ Carbon\Carbon::parse($licitacion->fecha_actualizacion)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Descripción -->
        @if ($licitacion->descripcion)
            <div class="mb-8">
                <h2 class="text-lg font-light mb-4 text-neutral-300">
                    <span class="text-neutral-400">&#9672;</span> Descripción
                </h2>
                <div class="p-6 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl">
                    <p class="text-neutral-300 leading-relaxed whitespace-pre-line">{{ $licitacion->descripcion }}</p>
                </div>
            </div>
        @endif

        <!-- Adjudicaciones -->
        <div class="mb-8">
            <h2 class="text-lg font-light mb-4 text-neutral-300">
                <span class="text-emerald-400">&#11045;</span> Adjudicaciones
            </h2>

            @if($licitacion->empresas->count() > 0)
                <div class="space-y-3">
                    @foreach ($licitacion->empresas as $empresa)
                        @if ($empresa->nombre)
                            <a href="{{ route('empresa.show', $empresa->id) }}"
                               class="group block p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl hover:bg-neutral-800/60 hover:border-emerald-500/30 transition-all duration-300">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex-1">
                                        <p class="font-medium text-neutral-200 group-hover:text-white transition-colors">
                                            {{ $empresa->nombre }}
                                        </p>
                                        @if($empresa->identificador)
                                            <p class="text-xs font-mono text-neutral-400 mt-1">{{ $empresa->identificador }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-mono text-xl text-emerald-400">
                                            {{ number_format($empresa->pivot->importe, 2, ',', '.') }}&euro;
                                        </p>
                                        @if($empresa->pivot->importe_final && $empresa->pivot->importe_final != $empresa->pivot->importe)
                                            <p class="text-xs text-neutral-400 mt-1">
                                                Final: {{ number_format($empresa->pivot->importe_final, 2, ',', '.') }}&euro;
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if($empresa->pivot->descripcion)
                                    <div class="p-3 bg-neutral-900/50 rounded-xl mt-3">
                                        <p class="text-sm text-neutral-400 leading-relaxed">{{ $empresa->pivot->descripcion }}</p>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-3 mt-3 text-xs">
                                    @if($empresa->pivot->tipo_procedimiento)
                                        <span class="px-2 py-1 bg-neutral-700/50 rounded-lg text-neutral-400">
                                            {{ $empresa->pivot->tipo_procedimiento }}
                                        </span>
                                    @endif
                                    @if($empresa->pivot->fecha_adjudicacion)
                                        <span class="px-2 py-1 bg-neutral-700/50 rounded-lg text-neutral-400 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                            {{ Carbon\Carbon::parse($empresa->pivot->fecha_adjudicacion)->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if($empresa->pivot->urgencia)
                                        <span class="px-2 py-1 bg-amber-500/20 rounded-lg text-amber-400 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                                            {{ $empresa->pivot->urgencia }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="py-16 text-center bg-neutral-800/30 border border-neutral-700/30 rounded-2xl">
                    <svg class="mx-auto h-12 w-12 text-neutral-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <p class="text-neutral-400 text-sm">No hay adjudicaciones registradas para esta licitación</p>
                    <p class="text-neutral-500 text-xs mt-1">Los datos se actualizan periódicamente</p>
                </div>
            @endif
        </div>

        <!-- Categoría si existe -->
        @if($licitacion->categoria)
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-800/50 rounded-full text-sm">
                <span class="text-neutral-400">Categoría:</span>
                <span class="text-neutral-300">{{ $licitacion->categoria->nombre }}</span>
            </div>
        @endif
        </div>

        <div class="flex-1 min-w-0">
            <div class="lg:sticky lg:top-8">
                @if($licitacion->datos_raiz)
                    <!-- Documentación Section -->
                    @if(count($parsedData['docs']) > 0)
                        <div class="mb-6">
                            <h2 class="text-lg font-light mb-4 text-neutral-300 flex items-center gap-2">
                                <span class="text-sky-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </span>
                                Documentación
                            </h2>
                            <div class="space-y-3">
                                @foreach($parsedData['docs'] as $doc)
                                    <a href="{{ $doc['url'] }}" target="_blank" class="group flex items-center justify-between p-4 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl hover:bg-neutral-800/80 hover:border-sky-500/30 transition-all duration-300">
                                        <div class="flex items-center gap-4 overflow-hidden">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-neutral-200 group-hover:text-white truncate transition-colors">{{ $doc['name'] }}</p>
                                                <p class="text-xs text-neutral-400 truncate">{{ $doc['label'] }}</p>
                                            </div>
                                        </div>
                                        <span class="text-neutral-400 group-hover:text-sky-400 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Información Adicional Section -->
                    <div class="mb-6 grid grid-cols-1 gap-4">
                        @if($parsedData['location'])
                            <div class="p-5 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                                <p class="text-xs text-neutral-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-neutral-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    Ubicación
                                </p>
                                <p class="text-lg text-neutral-200">{{ $parsedData['location'] }}</p>
                            </div>
                        @endif

                        @if($parsedData['deadline'] || $parsedData['contact'])
                            <div class="p-5 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                                <p class="text-xs text-neutral-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-neutral-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Contacto y Plazos
                                </p>

                                <div class="space-y-4">
                                    @if($parsedData['deadline'])
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1">Fecha Límite Presentación</p>
                                            <div class="flex items-center gap-2">
                                                <span class="text-amber-400 font-mono text-lg">
                                                    {{ Carbon\Carbon::parse($parsedData['deadline']['EndDate'])->format('d/m/Y') }}
                                                </span>
                                                @if(isset($parsedData['deadline']['EndTime']))
                                                    <span class="text-neutral-400 text-sm border-l border-neutral-700 pl-2">
                                                        {{ substr($parsedData['deadline']['EndTime'], 0, 5) }}h
                                                    </span>
                                                @endif
                                            </div>
                                            @if(isset($parsedData['deadline']['Description']))
                                                <p class="text-xs text-neutral-400 mt-1 leading-relaxed">{{ Str::limit($parsedData['deadline']['Description'], 100) }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if($parsedData['contact'])
                                        <div class="pt-3 border-t border-neutral-700/50">
                                            @if(isset($parsedData['contact']['ElectronicMail']))
                                                <a href="mailto:{{ $parsedData['contact']['ElectronicMail'] }}" class="flex items-center gap-2 text-sm text-sky-400 hover:text-sky-300 transition-colors mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                    </svg>
                                                    {{ $parsedData['contact']['ElectronicMail'] }}
                                                </a>
                                            @endif
                                            @if(isset($parsedData['contact']['Telephone']))
                                                <div class="flex items-center gap-2 text-sm text-neutral-400">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                                    </svg>
                                                    {{ $parsedData['contact']['Telephone'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                         @if($parsedData['duration'])
                            <div class="p-5 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                                <p class="text-xs text-neutral-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-neutral-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Duración
                                </p>
                                <p class="text-lg font-mono text-neutral-200">{{ $parsedData['duration'] }}</p>
                                @if($parsedData['extensions'])
                                    <div class="mt-3 pt-3 border-t border-neutral-700/50 text-xs text-neutral-400">
                                        <strong class="text-neutral-400">Prórrogas:</strong> {{ Str::limit($parsedData['extensions'], 150) }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if(count($parsedData['criteria']) > 0)
                            <div class="p-5 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                                <p class="text-xs text-neutral-400 uppercase tracking-wider mb-3">Criterios de Adjudicación</p>
                                <div class="space-y-3">
                                    @foreach($parsedData['criteria'] as $crit)
                                        <div>
                                            <div class="flex justify-between text-xs mb-1">
                                                <span class="text-neutral-300 truncate max-w-[70%]">{{ $crit['Description'] ?? 'Criterio' }}</span>
                                                <span class="text-emerald-400 font-mono">{{ $crit['WeightNumeric'] ?? '0' }}%</span>
                                            </div>
                                            <div class="h-1.5 bg-neutral-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-emerald-500/50 rounded-full" style="width: {{ $crit['WeightNumeric'] ?? 0 }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($parsedData['financialSolvency'] || count($parsedData['technicalSolvency']) > 0)
                             <div class="p-5 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                                <p class="text-xs text-neutral-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-neutral-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                    </svg>
                                    Requisitos de Solvencia
                                </p>
                                <div class="space-y-4 text-xs">
                                    @if($parsedData['financialSolvency'])
                                        <div>
                                            <p class="text-emerald-400 mb-1 font-medium">Económica</p>
                                            <p class="text-neutral-400 leading-relaxed">{{ Str::limit($parsedData['financialSolvency'], 200) }}</p>
                                        </div>
                                    @endif
                                    @if(count($parsedData['technicalSolvency']) > 0)
                                        <div>
                                            <p class="text-sky-400 mb-1 font-medium">Técnica</p>
                                            <ul class="list-disc list-inside text-neutral-400 space-y-1">
                                                @foreach($parsedData['technicalSolvency'] as $item)
                                                    <li>{{ Str::limit($item, 100) }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Toggle Header for RAW JSON -->
                    <button onclick="toggleJsonPanel()"
                            class="w-full mb-4 p-4 bg-neutral-800/50 hover:bg-neutral-800/80 border border-neutral-700/50 hover:border-purple-500/30 rounded-2xl transition-all duration-300 group">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-light text-neutral-300 flex items-center gap-2">
                                <span class="text-purple-400">&#9671;</span>
                                Datos Raw (JSON)
                                <span class="text-xs text-neutral-400 font-normal">(clic para ver)</span>
                            </h2>
                            <span id="jsonToggleIcon" class="text-purple-400 transition-transform duration-300">&#9654;</span>
                        </div>
                    </button>

                    <!-- Collapsible JSON Panel -->
                    <div id="jsonPanel" class="hidden">
                        <div class="mb-3 flex items-center justify-end gap-2">
                            <button onclick="expandAllJson()"
                                    class="px-3 py-1.5 text-xs bg-neutral-700/50 hover:bg-neutral-700 text-neutral-400 hover:text-neutral-200 rounded-lg transition-colors">
                                Expandir todo
                            </button>
                            <button onclick="collapseAllJson()"
                                    class="px-3 py-1.5 text-xs bg-neutral-700/50 hover:bg-neutral-700 text-neutral-400 hover:text-neutral-200 rounded-lg transition-colors">
                                Colapsar todo
                            </button>
                            <button onclick="copyJsonToClipboard()"
                                    class="px-3 py-1.5 text-xs bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 hover:text-purple-300 rounded-lg transition-colors flex items-center gap-1">
                                <svg id="copyIcon" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg>
                                <span id="copyText">Copiar</span>
                            </button>
                        </div>

                        <div class="bg-neutral-900/80 border border-neutral-700/50 rounded-2xl overflow-hidden">
                            <!-- Search bar -->
                            <div class="p-3 border-b border-neutral-700/50">
                                <input type="text"
                                       id="jsonSearch"
                                       placeholder="Buscar en JSON..."
                                       onkeyup="searchJson(this.value)"
                                       class="w-full px-3 py-2 bg-neutral-800/80 border border-neutral-600/50 rounded-lg text-sm text-neutral-200 placeholder-neutral-500 focus:outline-none focus:border-purple-500/50 transition-colors">
                            </div>

                            <!-- JSON viewer -->
                            <div id="jsonViewer" class="p-4 max-h-[calc(100vh-280px)] overflow-auto custom-scrollbar">
                                <pre class="text-sm font-mono leading-relaxed"></pre>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="py-16 text-center bg-neutral-800/30 border border-neutral-700/30 rounded-2xl">
                        <svg class="mx-auto h-12 w-12 text-neutral-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                        </svg>
                        <p class="text-neutral-400 text-sm">No hay datos raw disponibles para esta licitación</p>
                    </div>
                @endif
            </div>

            @include('partials.analisis_relacionados', ['analisis' => $analisis])
        </div>
    </div>

    @push('styles')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(38, 38, 38, 0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(82, 82, 82, 0.8);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(115, 115, 115, 0.8);
        }

        .json-key { color: #c084fc; }
        .json-string { color: #86efac; }
        .json-number { color: #fbbf24; }
        .json-boolean { color: #60a5fa; }
        .json-null { color: #9ca3af; }
        .json-bracket { color: #a3a3a3; }

        .json-collapsible {
            cursor: pointer;
            user-select: none;
        }
        .json-collapsible:hover {
            background: rgba(139, 92, 246, 0.1);
            border-radius: 4px;
        }
        .json-collapsed .json-content {
            display: none;
        }
        .json-collapsed .json-ellipsis {
            display: inline;
        }
        .json-ellipsis {
            display: none;
            color: #9ca3af;
        }
        .json-toggle {
            display: inline-block;
            width: 16px;
            text-align: center;
            color: #a78bfa;
        }

        .json-highlight {
            background: rgba(250, 204, 21, 0.3);
            border-radius: 2px;
            padding: 0 2px;
        }
    </style>
    @endpush

    <script>
        const rawJsonData = @json($licitacion->datos_raiz);
        let parsedJson = null;

        document.addEventListener('DOMContentLoaded', function() {
            try {
                parsedJson = typeof rawJsonData === 'string' ? JSON.parse(rawJsonData) : rawJsonData;
                renderJson(parsedJson);
            } catch (e) {
                document.querySelector('#jsonViewer pre').textContent = 'Error parsing JSON: ' + e.message;
            }
        });

        function renderJson(data, searchTerm = '') {
            const container = document.querySelector('#jsonViewer pre');
            container.innerHTML = formatValue(data, 0, searchTerm);
        }

        function formatValue(value, indent, searchTerm = '') {
            const spaces = '  '.repeat(indent);

            if (value === null) {
                return `<span class="json-null">null</span>`;
            }

            if (typeof value === 'boolean') {
                return `<span class="json-boolean">${value}</span>`;
            }

            if (typeof value === 'number') {
                return `<span class="json-number">${value}</span>`;
            }

            if (typeof value === 'string') {
                let displayValue = escapeHtml(value);
                if (searchTerm && displayValue.toLowerCase().includes(searchTerm.toLowerCase())) {
                    displayValue = highlightText(displayValue, searchTerm);
                }
                return `<span class="json-string">"${displayValue}"</span>`;
            }

            if (Array.isArray(value)) {
                if (value.length === 0) {
                    return `<span class="json-bracket">[]</span>`;
                }

                const id = 'json-' + Math.random().toString(36).substr(2, 9);
                let html = `<span class="json-collapsible" onclick="toggleJson('${id}')"><span class="json-toggle">&#9660;</span><span class="json-bracket">[</span></span>`;
                html += `<span class="json-ellipsis">...</span>`;
                html += `<span id="${id}" class="json-content">`;

                value.forEach((item, index) => {
                    html += `\n${spaces}  ${formatValue(item, indent + 1, searchTerm)}`;
                    if (index < value.length - 1) html += ',';
                });

                html += `\n${spaces}</span><span class="json-bracket">]</span>`;
                return html;
            }

            if (typeof value === 'object') {
                const keys = Object.keys(value);
                if (keys.length === 0) {
                    return `<span class="json-bracket">{}</span>`;
                }

                const id = 'json-' + Math.random().toString(36).substr(2, 9);
                let html = `<span class="json-collapsible" onclick="toggleJson('${id}')"><span class="json-toggle">&#9660;</span><span class="json-bracket">{</span></span>`;
                html += `<span class="json-ellipsis">...</span>`;
                html += `<span id="${id}" class="json-content">`;

                keys.forEach((key, index) => {
                    let displayKey = escapeHtml(key);
                    if (searchTerm && displayKey.toLowerCase().includes(searchTerm.toLowerCase())) {
                        displayKey = highlightText(displayKey, searchTerm);
                    }
                    html += `\n${spaces}  <span class="json-key">"${displayKey}"</span>: ${formatValue(value[key], indent + 1, searchTerm)}`;
                    if (index < keys.length - 1) html += ',';
                });

                html += `\n${spaces}</span><span class="json-bracket">}</span>`;
                return html;
            }

            return String(value);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function highlightText(text, searchTerm) {
            const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
            return text.replace(regex, '<span class="json-highlight">$1</span>');
        }

        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function toggleJson(id) {
            const element = document.getElementById(id);
            const parent = element.previousElementSibling.previousElementSibling;
            const toggle = parent.querySelector('.json-toggle');

            if (element.style.display === 'none') {
                element.style.display = 'inline';
                element.previousElementSibling.style.display = 'none';
                toggle.textContent = '\u25BC';
            } else {
                element.style.display = 'none';
                element.previousElementSibling.style.display = 'inline';
                toggle.textContent = '\u25B6';
            }
        }

        function expandAllJson() {
            document.querySelectorAll('.json-content').forEach(el => {
                el.style.display = 'inline';
                el.previousElementSibling.style.display = 'none';
            });
            document.querySelectorAll('.json-toggle').forEach(el => {
                el.textContent = '\u25BC';
            });
        }

        function collapseAllJson() {
            document.querySelectorAll('.json-content').forEach(el => {
                el.style.display = 'none';
                el.previousElementSibling.style.display = 'inline';
            });
            document.querySelectorAll('.json-toggle').forEach(el => {
                el.textContent = '\u25B6';
            });
        }

        function searchJson(term) {
            renderJson(parsedJson, term);
        }

        function copyJsonToClipboard() {
            const jsonStr = JSON.stringify(parsedJson, null, 2);
            navigator.clipboard.writeText(jsonStr).then(() => {
                const text = document.getElementById('copyText');
                text.textContent = 'Copiado!';
                setTimeout(() => {
                    text.textContent = 'Copiar';
                }, 2000);
            });
        }

        function toggleJsonPanel() {
            const panel = document.getElementById('jsonPanel');
            const icon = document.getElementById('jsonToggleIcon');

            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                icon.style.transform = 'rotate(90deg)';
            } else {
                panel.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
@endsection
