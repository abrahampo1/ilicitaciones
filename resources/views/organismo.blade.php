@extends('layouts.app')

@section('contenido')
    @section('meta_title', $organismo->nombre . ' - Licitaciones y Contratos - I-Licitaciones')
    @section('meta_description', $organismo->nombre . ': ' . number_format($totalLicitaciones, 0, ',', '.') . ' licitaciones por un total de ' . number_format($totalImporte, 0, ',', '.') . '€. Consulte presupuesto, adjudicaciones y estadísticas.')

    @push('json-ld')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "GovernmentOrganization",
        "name": "{{ str_replace('"', '\"', $organismo->nombre) }}",
        @if($organismo->direccion)"address": {
            "@@type": "PostalAddress",
            "streetAddress": "{{ str_replace('"', '\"', $organismo->direccion) }}",
            @if($organismo->codigo_postal)"postalCode": "{{ $organismo->codigo_postal }}",@endif
            @if($organismo->provincia)"addressRegion": "{{ $organismo->provincia }}",@endif
            "addressCountry": "{{ $organismo->pais ?? 'ES' }}"
        },@endif
        @if($organismo->contacto_email)"email": "{{ $organismo->contacto_email }}",@endif
        @if($organismo->contacto_telefono)"telephone": "{{ $organismo->contacto_telefono }}",@endif
        @if($organismo->sitio_web)"url": "{{ $organismo->sitio_web }}",@endif
        "identifier": "{{ $organismo->identificador }}"
    }
    </script>
    @endpush

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="text-xs text-neutral-500 flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:text-neutral-300 transition-colors">Inicio</a>
        <span>/</span>
        <a href="{{ route('organismos') }}" class="hover:text-neutral-300 transition-colors">Organismos</a>
        <span>/</span>
        <span class="text-neutral-300 truncate max-w-xs">{{ Str::limit($organismo->nombre, 40) }}</span>
    </nav>
@endsection

        <!-- Back navigation -->
        <a href="{{ route('organismos') }}"
            class="inline-flex items-center gap-2 text-neutral-400 hover:text-neutral-200 transition-colors mb-8 group">
            <span class="group-hover:-translate-x-1 transition-transform">&larr;</span>
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
                        <span class="font-mono text-neutral-400 bg-neutral-900/50 px-3 py-1 rounded-full border border-neutral-800">
                            ID: {{ $organismo->identificador }}
                        </span>
                    @endif
                    <span class="font-mono text-neutral-400">
                        #{{ $organismo->id }}
                    </span>
                </div>
            </div>
        </div>

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
                                        <svg class="w-4 h-4 shrink-0 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                        {{ $organismo->contacto_email }}
                                    </a>
                                @endif
                                @if($organismo->contacto_telefono)
                                    <p class="text-neutral-400 text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                        {{ $organismo->contacto_telefono }}
                                    </p>
                                @endif
                                @if($organismo->contacto_fax)
                                    <p class="text-neutral-400 text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.159 48.159 0 0110.5 0m-10.5 0V4.875c0-.621.504-1.125 1.125-1.125h8.25c.621 0 1.125.504 1.125 1.125v3.659" /></svg>
                                        {{ $organismo->contacto_fax }}
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
                                <span class="text-xs">&nearr;</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Últimas Licitaciones -->
                <div>
                    <h2 class="flex items-center gap-3 text-xl font-light mb-6 text-neutral-200">
                        <span class="text-neutral-400">&#9672;</span>
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
                                                {{ number_format($licitacion->importe_total, 0, ',', '.') }}&euro;
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-xs">
                                        @if($licitacion->fecha_actualizacion)
                                            <span class="flex items-center gap-1.5 text-neutral-400">
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
                        <div class="py-16 text-center bg-neutral-900/30 border border-neutral-800 rounded-2xl">
                            <svg class="mx-auto h-12 w-12 text-neutral-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <p class="text-neutral-400 text-sm">No hay licitaciones registradas para este organismo</p>
                            <p class="text-neutral-500 text-xs mt-1">Los datos se actualizan periódicamente</p>
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
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Licitaciones</p>
                        <p class="text-4xl font-mono text-cyan-400 font-light tracking-tight">
                            {{ number_format($totalLicitaciones, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="relative group bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2 font-medium">Volumen Total</p>
                        <p class="text-3xl font-mono text-emerald-400 font-light tracking-tight truncate" title="{{ number_format($totalImporte, 2, ',', '.') }}&euro;">
                            {{ number_format($totalImporte, 0, ',', '.') }}&euro;
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
                                        <span class="font-mono text-neutral-200 text-sm">{{ number_format($anual->total, 0, ',', '.') }}&euro;</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-neutral-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 rounded-full group-hover:brightness-125 transition-all duration-500 ease-out"
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-neutral-400 text-sm text-center py-4">Sin datos anuales</p>
                    @endif
                </div>

                @include('partials.analisis_relacionados', ['analisis' => $analisis])

            </div>
        </div>
@endsection
