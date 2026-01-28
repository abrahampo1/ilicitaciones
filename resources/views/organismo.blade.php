@extends('layouts.app')

@section('contenido')
    @section('meta_title', $organismo->nombre . ' - Licitaciones y Contratos - I-Licitaciones')
    @section('meta_description', 'Licitaciones y contratos de ' . $organismo->nombre . '. Consulte presupuesto, adjudicaciones y estadísticas del organismo.')

    <div class="max-w-5xl">
        <!-- Back navigation -->
        <a href="{{ route('organismos') }}"
            class="inline-flex items-center gap-2 text-neutral-500 hover:text-neutral-200 transition-colors mb-6 group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span>
            <span class="text-sm">Volver a organismos</span>
        </a>

        <!-- Header Section -->
        <div class="relative mb-8">
            <div
                class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl">
            </div>
            <div class="relative">
                <h1 class="text-2xl md:text-3xl font-light leading-tight mb-3 text-neutral-100">
                    {{ $organismo->nombre }} {{ $organismo->id }}
                </h1>
                @if($organismo->identificador)
                    <p class="font-mono text-neutral-500 text-sm">{{ $organismo->identificador }}</p>
                @endif
            </div>
        </div>

        <!-- Info del Organismo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            @if($organismo->direccion || $organismo->provincia)
                <div class="p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-3">📍 Ubicación</p>
                    <div class="text-neutral-300 space-y-1">
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
                <div class="p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-3">👤 Contacto</p>
                    <div class="space-y-2">
                        @if($organismo->contacto_nombre)
                            <p class="text-neutral-300">{{ $organismo->contacto_nombre }}</p>
                        @endif
                        @if($organismo->contacto_email)
                            <a href="mailto:{{ $organismo->contacto_email }}"
                                class="block text-cyan-400 hover:text-cyan-300 transition-colors text-sm">
                                ✉️ {{ $organismo->contacto_email }}
                            </a>
                        @endif
                        @if($organismo->contacto_telefono)
                            <p class="text-neutral-400 text-sm">📞 {{ $organismo->contacto_telefono }}</p>
                        @endif
                        @if($organismo->contacto_fax)
                            <p class="text-neutral-500 text-sm">📠 {{ $organismo->contacto_fax }}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if($organismo->sitio_web)
                <div class="p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl md:col-span-2">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-3">🌐 Sitio Web</p>
                    <a href="{{ $organismo->sitio_web }}" target="_blank" rel="noopener"
                        class="text-cyan-400 hover:text-cyan-300 transition-colors break-all">
                        {{ $organismo->sitio_web }}
                    </a>
                </div>
            @endif
        </div>

        <!-- Estadísticas -->
        @php
            $totalLicitaciones = $organismo->licitaciones()->count();
            $totalImporte = $organismo->licitaciones()->sum('importe_total');
            $licitaciones = $organismo->licitaciones()->latest('fecha_actualizacion')->limit(20)->get();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
            <div class="relative group">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-cyan-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div
                    class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-cyan-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Total Licitaciones</p>
                    <p class="text-3xl font-mono text-cyan-400">{{ number_format($totalLicitaciones, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="relative group">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div
                    class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-emerald-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Volumen Total</p>
                    <p class="text-3xl font-mono text-emerald-400">{{ number_format($totalImporte, 0, ',', '.') }}€</p>
                </div>
            </div>
        </div>

        <!-- Últimas Licitaciones -->
        <div class="mb-8">
            <h2 class="text-xl font-light mb-6 text-neutral-300">
                <span class="text-cyan-400">⬥</span> Últimas Licitaciones
            </h2>

            @if($licitaciones->count() > 0)
                <div class="space-y-3">
                    @foreach ($licitaciones as $licitacion)
                        <a href="{{ route('licitacion.show', $licitacion->id) }}"
                            class="group block p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl hover:bg-neutral-800/60 hover:border-cyan-500/30 transition-all duration-300">
                            <div class="flex items-start justify-between gap-4 mb-2">
                                <div class="flex-1 min-w-0">
                                    <p class="font-light text-neutral-200 group-hover:text-white transition-colors line-clamp-2">
                                        {{ $licitacion->titulo }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-mono text-emerald-400">
                                        {{ number_format($licitacion->importe_total, 0, ',', '.') }}€
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs">
                                @if($licitacion->fecha_actualizacion)
                                    <span class="text-neutral-500">
                                        {{ Carbon\Carbon::parse($licitacion->fecha_actualizacion)->format('d/m/Y') }}
                                    </span>
                                @endif
                                @if($licitacion->estado)
                                    <span class="px-2 py-1 rounded-full 
                                                        @if($licitacion->estado == 'Adjudicada') bg-emerald-500/20 text-emerald-400
                                                        @elseif($licitacion->estado == 'Evaluación') bg-amber-500/20 text-amber-400
                                                        @elseif($licitacion->estado == 'Publicada') bg-sky-500/20 text-sky-400
                                                        @else bg-neutral-500/20 text-neutral-400 @endif">
                                        {{ $licitacion->estado }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-8 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl text-center">
                    <p class="text-neutral-500">No hay licitaciones registradas para este organismo</p>
                </div>
            @endif
        </div>
    </div>
@endsection