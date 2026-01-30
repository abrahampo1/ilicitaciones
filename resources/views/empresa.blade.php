@extends('layouts.app')

@section('contenido')
@section('meta_title', $empresa->nombre . ' - Adjudicaciones - I-Licitaciones')
@section('meta_description', 'Adjudicaciones y contratos de ' . $empresa->nombre . '. Historial de licitaciones ganadas e importes totales.')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back navigation -->
        <a href="{{ route('empresas') }}"
            class="inline-flex items-center gap-2 text-neutral-400 hover:text-neutral-200 transition-colors mb-8 group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span>
            <span class="text-sm">Volver a empresas</span>
        </a>

        <!-- Header Section -->
        <div class="relative mb-12">
            <div
                class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-cyan-500/5 to-transparent rounded-3xl blur-3xl pointer-events-none">
            </div>
            <div class="relative">
                <h1 class="text-3xl md:text-4xl font-light leading-tight mb-3 text-neutral-100">
                    {{ $empresa->nombre }}
                </h1>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    @if($empresa->identificador)
                        <span
                            class="font-mono text-neutral-400 bg-neutral-900/50 px-3 py-1 rounded-full border border-neutral-800">
                            ID: {{ $empresa->identificador }}
                        </span>
                    @endif
                    <span class="font-mono text-neutral-400">
                        #{{ $empresa->id }}
                    </span>
                </div>
            </div>
        </div>

        @php
            // Logic for stats
            $adjudicacionesQuery = App\Models\Adjudicacion::where('empresa_id', $empresa->id);

            // Clone query/use distinct queries to avoiding issues if we add groups later, though here it's simple
            $totalImporte = (clone $adjudicacionesQuery)->sum('importe');
            $totalAdjudicaciones = (clone $adjudicacionesQuery)->count();
            $importeMedio = $totalAdjudicaciones > 0 ? $totalImporte / $totalAdjudicaciones : 0;

            // Get list
            $adjudicaciones = $adjudicacionesQuery
                ->with('licitacion.organismo')
                ->orderByDesc('fecha_adjudicacion') // Better sort by date
                ->limit(50) // Limit for performance
                ->get();

            // Logic for annual breakdown (Empresas)
            // Using fecha_adjudicacion from adjudicacions table
            $inversionAnual = App\Models\Adjudicacion::where('empresa_id', $empresa->id)
                ->selectRaw('YEAR(fecha_adjudicacion) as year, SUM(importe) as total')
                ->whereNotNull('fecha_adjudicacion')
                ->groupBy('year')
                ->orderByDesc('year')
                ->get();

            $maxYearlyTotal = $inversionAnual->max('total');
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Adjudicaciones List (Span 2) -->
            <div class="lg:col-span-2">
                <h2 class="flex items-center gap-3 text-xl font-light mb-6 text-neutral-200">
                    <span class="text-emerald-400">◈</span>
                    Historial de Adjudicaciones
                </h2>

                @if($adjudicaciones->count() > 0)
                    <div class="space-y-4">
                        @foreach ($adjudicaciones as $adj)
                            <a href="{{ route('licitacion.show', $adj->licitacion_id) }}"
                                class="group block p-5 bg-neutral-900/30 border border-neutral-800 rounded-2xl hover:bg-neutral-800 hover:border-emerald-500/30 transition-all duration-300">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h3
                                            class="font-normal text-neutral-200 group-hover:text-white transition-colors line-clamp-2 leading-relaxed">
                                            {{ $adj->licitacion->titulo ?? 'Sin título' }}
                                        </h3>
                                        @if($adj->licitacion && $adj->licitacion->organismo)
                                            <p class="text-xs text-cyan-400/70 mt-2 truncate flex items-center gap-1">
                                                <span>🏛️</span> {{ Str::limit($adj->licitacion->organismo->nombre, 60) }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="md:text-right shrink-0">
                                        <p class="font-mono text-lg text-emerald-400 tabular-nums font-medium">
                                            {{ number_format($adj->importe, 0, ',', '.') }}€
                                        </p>
                                        @if($adj->importe_final && $adj->importe_final != $adj->importe)
                                            <p class="text-xs text-neutral-400 mt-1">
                                                Final: {{ number_format($adj->importe_final, 0, ',', '.') }}€
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-xs">
                                    @if($adj->fecha_adjudicacion)
                                        <span class="flex items-center gap-1.5 text-neutral-400">
                                            <span class="w-1 h-1 rounded-full bg-neutral-600"></span>
                                            {{ Carbon\Carbon::parse($adj->fecha_adjudicacion)->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if($adj->tipo_procedimiento)
                                        <span
                                            class="px-2.5 py-0.5 rounded-full border border-neutral-700/50 bg-neutral-800/50 text-neutral-400">
                                            {{ $adj->tipo_procedimiento }}
                                        </span>
                                    @endif
                                    @if($adj->urgencia)
                                        <span
                                            class="px-2.5 py-0.5 rounded-full border border-amber-500/20 bg-amber-500/10 text-amber-400">
                                            ⚡ {{ $adj->urgencia }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 bg-neutral-900/30 border border-neutral-800 rounded-2xl text-center">
                        <p class="text-neutral-400">No hay adjudicaciones registradas para esta empresa</p>
                    </div>
                @endif
            </div>

            <!-- Right Column: Stats & Breakdown (Span 1) -->
            <div class="space-y-6">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity">
                        </div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Importe Total</p>
                        <p class="text-3xl font-mono text-emerald-400 font-light tracking-tight truncate"
                            title="{{ number_format($totalImporte, 2, ',', '.') }}€">
                            {{ number_format($totalImporte, 0, ',', '.') }}€
                        </p>
                    </div>

                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-sky-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity">
                        </div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Adjudicaciones</p>
                        <p class="text-4xl font-mono text-sky-400 font-light tracking-tight">
                            {{ number_format($totalAdjudicaciones, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-teal-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity">
                        </div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Importe Medio</p>
                        <p class="text-3xl font-mono text-teal-400 font-light tracking-tight truncate">
                            {{ number_format($importeMedio, 0, ',', '.') }}€
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
                                        <span
                                            class="font-mono text-neutral-200 text-sm">{{ number_format($anual->total, 0, ',', '.') }}€</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-neutral-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-sky-500 to-emerald-500 rounded-full group-hover:brightness-125 transition-all duration-500 ease-out"
                                            style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-neutral-400 text-sm text-center py-4">Sin datos anuales</p>
                    @endif
                </div>

                <!-- Related Companies -->
                <div class="bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6">
                    <h3 class="flex items-center gap-2 text-neutral-300 text-sm font-medium mb-2">
                        Empresas Relacionadas
                    </h3>
                    <p class="text-xs text-neutral-400 mb-6">
                        Tienen el mismo identificador pero distinto nombre.
                    </p>

                    @php
                        $relatedCompanies = App\Models\Empresa::where('identificador', $empresa->identificador)->whereNot('id', $empresa->id)->get();
                       @endphp

                    @foreach($relatedCompanies as $relatedCompany)
                        <div
                            class="flex items-center gap-2 p-1 px-2 mt-2 border border-neutral-800 rounded-lg hover:bg-neutral-800 transition-colors">
                            <a href="{{ route('empresa.show', $relatedCompany->id) }}" class="flex items-center gap-2">
                                <div>
                                    <p class="font-medium text-neutral-200">{{ $relatedCompany->nombre }}</p>
                                    <p class="text-xs text-neutral-400">{{ $relatedCompany->identificador }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                    
                    @if ($relatedCompanies->isEmpty())
                        <p class="text-center italic text-xs">No hay empresas relacionadas</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection