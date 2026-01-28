@extends('layouts.app')

@section('contenido')
    @section('meta_title', 'I-Licitaciones | Inteligencia de Mercado Público')
    @section('meta_description', 'Panel de inteligencia de licitaciones. Analiza adjudicaciones, organismos y empresas del sector público en tiempo real.')

    <!-- Hero Stats Section -->
    <div class="relative mb-12">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <p class="text-neutral-500 text-xs md:text-sm mb-2">Hasta el {{ $stats['latestDate'] ? \Carbon\Carbon::parse($stats['latestDate'])->format('d/m/Y H:i') : 'N/A' }}</p>
            <h2 class="text-3xl md:text-5xl font-light mb-8 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Panel de Licitaciones
            </h2>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-emerald-500/30 transition-all duration-300">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Licitaciones</p>
                    <p class="text-2xl md:text-3xl font-mono text-emerald-400">{{ number_format($stats['conteoLicitaciones'], 0, ',', '.') }}</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-teal-500/30 transition-all duration-300">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Volumen Total</p>
                    <p class="text-xl md:text-2xl font-mono text-teal-400">{{ number_format($stats['totalImporte'], 2, ',', '.') }}€</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-cyan-500/30 transition-all duration-300">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Organismos</p>
                    <p class="text-2xl md:text-3xl font-mono text-cyan-400">{{ number_format($stats['totalOrganismos'], 0, ',', '.') }}</p>
                </div>
                <div class="group p-6 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl hover:border-sky-500/30 transition-all duration-300">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Empresas</p>
                    <p class="text-2xl md:text-3xl font-mono text-sky-400">{{ number_format($stats['totalEmpresas'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Top Empresas -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-light">
                        <span class="text-emerald-400">⬥</span> Top 10 Empresas
                    </h3>
                    <a href="{{ route('empresas') }}" class="text-xs text-neutral-500 hover:text-emerald-400 transition-colors">
                        Ver todas →
                    </a>
                </div>
                
                <div class="space-y-1">
                    @foreach ($topEmpresas as $index => $adjudicacion)
                        <a href="{{ route('empresa.show', $adjudicacion->empresa_id) }}" 
                           class="group flex items-center py-3 px-3 -mx-3 rounded-xl hover:bg-neutral-800/50 transition-all duration-200">
                            <span class="w-6 text-neutral-600 text-sm font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="flex-1 font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ Str::limit($adjudicacion->empresa->nombre ?? 'N/A', 35) }}
                            </span>
                            <span class="font-mono text-sm text-emerald-400/80 group-hover:text-emerald-400 transition-colors">
                                {{ number_format($adjudicacion->total_importe, 0, ',', '.') }}€
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Organismos -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-cyan-600/20 to-sky-600/20 rounded-3xl blur-xl opacity-50"></div>
            <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-light">
                        <span class="text-cyan-400">⬥</span> Top 10 Organismos
                    </h3>
                    <a href="{{ route('organismos') }}" class="text-xs text-neutral-500 hover:text-cyan-400 transition-colors">
                        Ver todos →
                    </a>
                </div>
                
                <div class="space-y-1">
                    @foreach ($topOrganismos as $index => $organismo)
                        <a href="{{ route('organismo.show', $organismo->organismo->id ?? $organismo->organismo_id) }}" 
                           class="group flex items-center py-3 px-3 -mx-3 rounded-xl hover:bg-neutral-800/50 transition-all duration-200">
                            <span class="w-6 text-neutral-600 text-sm font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="flex-1 font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ Str::limit($organismo->organismo->nombre ?? 'N/A', 35) }}
                            </span>
                            <span class="font-mono text-sm text-cyan-400/80 group-hover:text-cyan-400 transition-colors">
                                {{ number_format($organismo->total_importe ?? 0, 0, ',', '.') }}€
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Licitaciones -->
    <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-light">
                <span class="text-neutral-600">◈</span> Últimas Licitaciones
            </h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($ultimasLicitaciones as $lic)
                <a href="{{ route('licitacion.show', $lic->id) }}" 
                   class="group p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl hover:bg-neutral-800/60 hover:border-neutral-600/50 transition-all duration-300">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-light text-neutral-200 group-hover:text-white transition-colors line-clamp-2 mb-2">
                                {{ Str::limit($lic->titulo, 80) }}
                            </p>
                            <p class="text-xs text-neutral-500 truncate">
                                {{ Str::limit($lic->organismo->nombre ?? 'Sin organismo', 40) }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-mono text-emerald-400 text-sm">
                                {{ number_format($lic->importe_total, 0, ',', '.') }}€
                            </p>
                            <p class="text-xs text-neutral-500 mt-1">
                                {{ $lic->fecha_actualizacion ? \Carbon\Carbon::parse($lic->fecha_actualizacion)->format('d/m/Y') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        @php
                            $estadoClass = match($lic->estado) {
                                'Adjudicada' => 'bg-emerald-500/10 text-emerald-400',
                                'Evaluación' => 'bg-amber-500/10 text-amber-400',
                                'Publicada' => 'bg-sky-500/10 text-sky-400',
                                default => 'bg-neutral-500/10 text-neutral-400',
                            };
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $estadoClass }}">
                            {{ $lic->estado ?? 'Sin estado' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection