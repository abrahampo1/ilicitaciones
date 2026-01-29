@extends('layouts.app')

@section('contenido')
    @section('meta_title', $organismo->nombre . ' - Licitaciones y Contratos - I-Licitaciones')
    @section('meta_description', 'Licitaciones y contratos de ' . $organismo->nombre . '. Consulte presupuesto, adjudicaciones y estadísticas del organismo.')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back navigation -->
        <a href="{{ route('organismos') }}"
            class="inline-flex items-center gap-2 text-neutral-500 hover:text-neutral-200 transition-colors mb-8 group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span>
            <span class="text-sm">Volver a organismos</span>
        </a>

        <!-- Header Section -->
        <div class="relative mb-12">
            <div
                class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl pointer-events-none">
            </div>
            <div class="relative">
                <h1 class="text-3xl md:text-4xl font-light leading-tight mb-3 text-neutral-100">
                    {{ $organismo->nombre }}
                </h1>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    @if($organismo->identificador)
                        <span class="font-mono text-neutral-500 bg-neutral-900/50 px-3 py-1 rounded-full border border-neutral-800">
                            ID: {{ $organismo->identificador }}
                        </span>
                    @endif
                    <span class="font-mono text-neutral-500">
                        #{{ $organismo->id }}
                    </span>
                </div>
            </div>
        </div>

        @php
            // Logic for stats
            $totalLicitaciones = $organismo->licitaciones()->count();
            $totalImporte = $organismo->licitaciones()->sum('importe_total');
            $licitaciones = $organismo->licitaciones()->latest('fecha_actualizacion')->limit(20)->get();
            
            // Logic for annual breakdown
            $inversionAnual = $organismo->licitaciones()
                ->selectRaw('YEAR(fecha_actualizacion) as year, SUM(importe_total) as total')
                ->whereNotNull('fecha_actualizacion')
                ->groupBy('year')
                ->orderByDesc('year')
                ->get();
                
            $maxYearlyTotal = $inversionAnual->max('total');
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Main Info & Licitaciones (Span 2) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Info del Organismo -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($organismo->direccion || $organismo->provincia)
                        <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
                            <h3 class="flex items-center gap-2 text-neutral-400 text-xs uppercase tracking-wider mb-4 font-semibold">
                                 Ubicación
                            </h3>
                            <div class="text-neutral-300 space-y-1 text-sm leading-relaxed">
                                @if($organismo->direccion)
                                    <p>{{ $organismo->direccion }}</p>
                                @endif
                                <p>
                                    @if($organismo->codigo_postal){{ $organismo->codigo_postal }} @endif
                                    @if($organismo->provincia){{ $organismo->provincia }}@endif
                                    @if($organismo->pais), {{ $organismo->pais }}@endif
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($organismo->contacto_nombre || $organismo->contacto_email || $organismo->contacto_telefono)
                        <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl">
                            <h3 class="flex items-center gap-2 text-neutral-400 text-xs uppercase tracking-wider mb-4 font-semibold">
                                 Contacto
                            </h3>
                            <div class="space-y-3">
                                @if($organismo->contacto_nombre)
                                    <p class="text-neutral-300 text-sm">{{ $organismo->contacto_nombre }}</p>
                                @endif
                                @if($organismo->contacto_email)
                                    <a href="mailto:{{ $organismo->contacto_email }}"
                                        class="flex items-center gap-2 text-cyan-400 hover:text-cyan-300 transition-colors text-sm group">
                                        <span class="group-hover:scale-110 transition-transform">✉️</span> {{ $organismo->contacto_email }}
                                    </a>
                                @endif
                                @if($organismo->contacto_telefono)
                                    <p class="text-neutral-400 text-sm flex items-center gap-2">
                                        <span>📞</span> {{ $organismo->contacto_telefono }}
                                    </p>
                                @endif
                                @if($organismo->contacto_fax)
                                    <p class="text-neutral-500 text-sm flex items-center gap-2">
                                        <span>📠</span> {{ $organismo->contacto_fax }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($organismo->sitio_web)
                        <div class="p-6 bg-neutral-900/50 border border-neutral-800 rounded-2xl md:col-span-2">
                            <h3 class="flex items-center gap-2 text-neutral-400 text-xs uppercase tracking-wider mb-4 font-semibold">
                                Sitio Web
                            </h3>
                            <a href="{{ $organismo->sitio_web }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 text-cyan-400 hover:text-cyan-300 transition-colors break-all">
                                {{ $organismo->sitio_web }}
                                <span class="text-xs">↗</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Últimas Licitaciones -->
                <div>
                    <h2 class="flex items-center gap-3 text-xl font-light mb-6 text-neutral-200">
                        <span class="text-neutral-600">◈</span>
                        Últimas Licitaciones
                    </h2>

                    @if($licitaciones->count() > 0)
                        <div class="space-y-4">
                            @foreach ($licitaciones as $licitacion)
                                <a href="{{ route('licitacion.show', $licitacion->id) }}"
                                    class="group block p-5 bg-neutral-900/30 border border-neutral-800 rounded-2xl hover:bg-neutral-800 hover:border-cyan-500/30 transition-all duration-300">
                                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-normal text-neutral-200 group-hover:text-white transition-colors line-clamp-2 leading-relaxed">
                                                {{ $licitacion->titulo }}
                                            </h3>
                                        </div>
                                        <div class="md:text-right shrink-0">
                                            <p class="font-mono text-lg text-emerald-400 tabular-nums font-medium">
                                                {{ number_format($licitacion->importe_total, 0, ',', '.') }}€
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-xs">
                                        @if($licitacion->fecha_actualizacion)
                                            <span class="flex items-center gap-1.5 text-neutral-500">
                                                <span class="w-1 h-1 rounded-full bg-neutral-600"></span>
                                                {{ Carbon\Carbon::parse($licitacion->fecha_actualizacion)->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        
                                        @if($licitacion->estado)
                                            <span class="px-2.5 py-0.5 rounded-full border border-transparent
                                                @if($licitacion->estado == 'Adjudicada') bg-emerald-500/10 text-emerald-400 border-emerald-500/20
                                                @elseif($licitacion->estado == 'Evaluación') bg-amber-500/10 text-amber-400 border-amber-500/20
                                                @elseif($licitacion->estado == 'Publicada') bg-sky-500/10 text-sky-400 border-sky-500/20
                                                @else bg-neutral-500/10 text-neutral-400 border-neutral-500/20 @endif">
                                                {{ $licitacion->estado }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="p-12 bg-neutral-900/30 border border-neutral-800 rounded-2xl text-center">
                            <p class="text-neutral-500">No hay licitaciones registradas para este organismo.</p>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Right Column: Stats & Breakdown (Span 1) -->
            <div class="space-y-6">
                
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2 font-medium">Licitaciones</p>
                        <p class="text-4xl font-mono text-cyan-400 font-light tracking-tight">
                            {{ number_format($totalLicitaciones, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2 font-medium">Volumen Total</p>
                        <p class="text-3xl font-mono text-emerald-400 font-light tracking-tight truncate" title="{{ number_format($totalImporte, 2, ',', '.') }}€">
                            {{ number_format($totalImporte, 0, ',', '.') }}€
                        </p>
                    </div>
                </div>

                <!-- Annual Investment Breakdown -->
                <div class="bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6">
                    <h3 class="flex items-center gap-2 text-neutral-300 text-sm font-medium mb-6">
                        Inversión Anual
                    </h3>
                    
                    @if($inversionAnual->count() > 0)
                        <div class="space-y-5">
                            @foreach($inversionAnual as $anual)
                                @php
                                    $percentage = $maxYearlyTotal > 0 ? ($anual->total / $maxYearlyTotal) * 100 : 0;
                                @endphp
                                <div class="group relative">
                                    <div class="flex justify-between items-end mb-1">
                                        <span class="font-mono text-neutral-400 text-sm">{{ $anual->year }}</span>
                                        <span class="font-mono text-neutral-200 text-sm">{{ number_format($anual->total, 0, ',', '.') }}€</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-neutral-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 rounded-full group-hover:brightness-125 transition-all duration-500 ease-out"
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-neutral-500 text-sm text-center py-4">Sin datos anuales</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection
