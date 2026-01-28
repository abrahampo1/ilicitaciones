@extends('layouts.app')

@section('contenido')
    @section('meta_title', $empresa->nombre . ' - Adjudicaciones - I-Licitaciones')
    @section('meta_description', 'Adjudicaciones y contratos de ' . $empresa->nombre . '. Historial de licitaciones ganadas e importes totales.')

    <div class="max-w-5xl">
        <!-- Back navigation -->
        <a href="{{ route('empresas') }}" 
           class="inline-flex items-center gap-2 text-neutral-500 hover:text-neutral-200 transition-colors mb-6 group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span>
            <span class="text-sm">Volver a empresas</span>
        </a>

        <!-- Header Section -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-cyan-500/5 to-transparent rounded-3xl blur-3xl"></div>
            <div class="relative">
                <h1 class="text-2xl md:text-3xl font-light leading-tight mb-3 text-neutral-100">
                    {{ $empresa->nombre }}
                </h1>
                @if($empresa->identificador)
                    <p class="font-mono text-neutral-500 text-sm">{{ $empresa->identificador }}</p>
                @endif
            </div>
        </div>

        <!-- Estadísticas -->
        @php
            $adjudicaciones = App\Models\Adjudicacion::where('empresa_id', $empresa->id)
                ->with('licitacion.organismo')
                ->orderByDesc('importe')
                ->get();
            $totalImporte = $adjudicaciones->sum('importe');
            $totalAdjudicaciones = $adjudicaciones->count();
            $importeMedio = $totalAdjudicaciones > 0 ? $totalImporte / $totalAdjudicaciones : 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-emerald-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Importe Total</p>
                    <p class="text-2xl font-mono text-emerald-400">{{ number_format($totalImporte, 2, ',', '.') }}€</p>
                </div>
            </div>
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-sky-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-sky-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Adjudicaciones</p>
                    <p class="text-2xl font-mono text-sky-400">{{ $totalAdjudicaciones }}</p>
                </div>
            </div>
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-teal-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-teal-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Importe Medio</p>
                    <p class="text-2xl font-mono text-teal-400">{{ number_format($importeMedio, 0, ',', '.') }}€</p>
                </div>
            </div>
        </div>

        <!-- Adjudicaciones -->
        <div class="mb-8">
            <h2 class="text-xl font-light mb-6 text-neutral-300">
                <span class="text-emerald-400">⬥</span> Historial de Adjudicaciones
            </h2>
            
            @if($adjudicaciones->count() > 0)
                <div class="space-y-3">
                    @foreach ($adjudicaciones as $adj)
                        <a href="{{ route('licitacion.show', $adj->licitacion_id) }}" 
                           class="group block p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl hover:bg-neutral-800/60 hover:border-emerald-500/30 transition-all duration-300">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-light text-neutral-200 group-hover:text-white transition-colors line-clamp-2">
                                        {{ $adj->licitacion->titulo ?? 'Sin título' }}
                                    </p>
                                    @if($adj->licitacion && $adj->licitacion->organismo)
                                        <p class="text-xs text-cyan-400/70 mt-1 truncate">
                                            {{ Str::limit($adj->licitacion->organismo->nombre, 60) }}
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-mono text-xl text-emerald-400">
                                        {{ number_format($adj->importe, 2, ',', '.') }}€
                                    </p>
                                    @if($adj->importe_final && $adj->importe_final != $adj->importe)
                                        <p class="text-xs text-neutral-500">
                                            Final: {{ number_format($adj->importe_final, 2, ',', '.') }}€
                                        </p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-3 text-xs">
                                @if($adj->fecha_adjudicacion)
                                    <span class="px-2 py-1 bg-neutral-700/50 rounded-lg text-neutral-400">
                                        📅 {{ Carbon\Carbon::parse($adj->fecha_adjudicacion)->format('d/m/Y') }}
                                    </span>
                                @endif
                                @if($adj->tipo_procedimiento)
                                    <span class="px-2 py-1 bg-neutral-700/50 rounded-lg text-neutral-400">
                                        {{ $adj->tipo_procedimiento }}
                                    </span>
                                @endif
                                @if($adj->urgencia)
                                    <span class="px-2 py-1 bg-amber-500/20 rounded-lg text-amber-400">
                                        ⚡ {{ $adj->urgencia }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-8 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl text-center">
                    <p class="text-neutral-500">No hay adjudicaciones registradas para esta empresa</p>
                </div>
            @endif
        </div>
    </div>
@endsection
