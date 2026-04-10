@extends('layouts.app')

@section('meta_title', 'Página no encontrada - I-Licitaciones')
@section('meta_description', 'La página que buscas no existe o ha sido movida.')

@section('contenido')
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <p class="text-7xl font-mono text-neutral-600 mb-4">404</p>
        <h1 class="text-2xl font-light text-neutral-200 mb-3">Página no encontrada</h1>
        <p class="text-neutral-400 text-sm mb-8 max-w-md">
            La página que buscas no existe o ha sido movida. Puedes volver al inicio o explorar las secciones disponibles.
        </p>
        <div class="flex gap-4">
            <a href="{{ route('home') }}"
               class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl transition-colors text-sm">
                Ir al inicio
            </a>
            <a href="{{ route('organismos') }}"
               class="px-6 py-2.5 bg-neutral-700 hover:bg-neutral-600 text-neutral-200 rounded-xl transition-colors text-sm">
                Ver organismos
            </a>
        </div>
    </div>
@endsection
