@php
    $empresas = App\Models\Adjudicacion::selectRaw('empresa_id, SUM(importe) as total_importe, COUNT(*) as total_adjudicaciones')
        ->groupBy('empresa_id')
        ->orderByDesc('total_importe')
        ->with('empresa')
        ->paginate(30);
    
    $totalVolumen = App\Models\Adjudicacion::sum('importe');
    $totalEmpresas = App\Models\Empresa::count();
@endphp

@extends('layouts.app')

@section('contenido')
    <!-- Hero Section -->
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 via-cyan-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h2 class="text-4xl md:text-5xl font-light mb-4 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Empresas
            </h2>
            <p class="text-neutral-500 mb-6">Ranking de empresas por volumen de adjudicaciones</p>
            
            <!-- Stats -->
            <div class="flex flex-wrap gap-4 mb-8">
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-500 text-xs uppercase tracking-wider">Total Empresas</span>
                    <p class="text-2xl font-mono text-sky-400">{{ number_format($totalEmpresas, 0, ',', '.') }}</p>
                </div>
                <div class="px-5 py-3 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl">
                    <span class="text-neutral-500 text-xs uppercase tracking-wider">Volumen Adjudicado</span>
                    <p class="text-2xl font-mono text-emerald-400">{{ number_format($totalVolumen, 0, ',', '.') }}€</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Empresas -->
    <div class="relative">
        <div class="absolute -inset-1 bg-gradient-to-r from-sky-600/10 to-cyan-600/10 rounded-3xl blur-xl opacity-50"></div>
        <div class="relative bg-neutral-900/90 backdrop-blur border border-neutral-700/50 rounded-2xl p-6">
            <div class="space-y-1">
                @foreach ($empresas as $index => $item)
                    <a href="{{ route('empresa.show', $item->empresa_id) }}" 
                       class="group flex items-center py-4 px-4 -mx-4 rounded-xl hover:bg-neutral-800/50 transition-all duration-200 border-b border-neutral-800 last:border-none">
                        <span class="w-10 text-neutral-600 text-sm font-mono">
                            {{ str_pad($empresas->firstItem() + $index, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-light text-neutral-300 group-hover:text-white transition-colors truncate">
                                {{ $item->empresa->nombre }}
                            </p>
                            @if($item->empresa->identificador)
                                <p class="text-xs font-mono text-neutral-600 mt-0.5">{{ $item->empresa->identificador }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0 ml-4">
                            <p class="font-mono text-emerald-400 group-hover:text-emerald-300 transition-colors">
                                {{ number_format($item->total_importe, 0, ',', '.') }}€
                            </p>
                            <p class="text-xs text-neutral-500">{{ $item->total_adjudicaciones }} adj.</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Paginación -->
    <div class="mt-8 flex justify-center">
        {{ $empresas->links() }}
    </div>
@endsection
