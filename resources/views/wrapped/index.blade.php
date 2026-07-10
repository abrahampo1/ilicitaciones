@extends('layouts.app')

@section('meta_title', 'Wrapped · El gasto público de cada año - I-Licitaciones')
@section('meta_description', 'El resumen anual del gasto público en España al estilo Wrapped: totales, organismos que más licitan, empresas más adjudicadas y récords de cada año.')

@section('contenido')
    @php
        use App\Support\Formato;

        // Paleta rotatoria de gradientes para las tarjetas de año.
        $gradientes = [
            'from-fuchsia-600 via-purple-600 to-indigo-700',
            'from-amber-500 via-orange-600 to-rose-600',
            'from-emerald-500 via-teal-600 to-cyan-700',
            'from-sky-500 via-blue-600 to-violet-700',
            'from-rose-500 via-pink-600 to-fuchsia-700',
            'from-lime-500 via-emerald-600 to-teal-700',
        ];
    @endphp

    <div class="max-w-4xl mx-auto">
        <header class="text-center mb-12">
            <p class="text-sm uppercase tracking-[0.3em] text-neutral-400 mb-3">I-Licitaciones presenta</p>
            <h1 class="text-5xl sm:text-6xl font-black tracking-tight mb-4">
                <span class="bg-gradient-to-r from-fuchsia-400 via-amber-300 to-emerald-400 bg-clip-text text-transparent">Wrapped</span>
            </h1>
            <p class="text-lg text-neutral-300 max-w-2xl mx-auto">
                El resumen anual del gasto público español, contado en historias.
                Elige un año y descubre quién gastó, quién ganó y cuánto costó.
            </p>
        </header>

        @if ($years->isEmpty())
            <div class="text-center py-16 border border-neutral-800 rounded-2xl">
                <p class="text-neutral-400">Todavía no hay datos suficientes para generar ningún wrapped.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                @foreach ($years as $i => $y)
                    <a href="{{ route('wrapped.show', ['year' => $y]) }}"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $gradientes[$i % count($gradientes)] }} p-6 sm:p-8 min-h-44 flex flex-col justify-between transition-transform duration-300 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-white/70">
                        <div class="absolute -right-6 -bottom-10 text-[9rem] leading-none font-black text-white/10 select-none pointer-events-none group-hover:text-white/15 transition-colors">
                            {{ $y }}
                        </div>
                        <div class="relative">
                            <p class="text-xs uppercase tracking-[0.25em] text-white/70 mb-1">Wrapped</p>
                            <p class="text-4xl font-black text-white">{{ $y }}</p>
                        </div>
                        <div class="relative">
                            @if (isset($totalesPorYear[$y]))
                                <p class="text-white/80 text-sm">Dinero público adjudicado</p>
                                <p class="text-xl font-bold text-white">{{ Formato::eurosCompactos((float) $totalesPorYear[$y]) }}</p>
                            @endif
                            <p class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-white group-hover:gap-2 transition-all">
                                Ver el wrapped <span aria-hidden="true">→</span>
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
