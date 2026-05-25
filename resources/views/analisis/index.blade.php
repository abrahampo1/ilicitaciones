@extends('layouts.app')

@section('meta_title', 'Análisis | I-Licitaciones')
@section('meta_description', 'Periodismo de datos sobre la contratación pública en España: rankings, alertas, informes sectoriales y perfiles de empresas y organismos.')

@section('breadcrumbs')
    <nav aria-label="Migas de pan" class="text-xs text-neutral-500">
        <a href="{{ route('home') }}" class="hover:text-neutral-300">Inicio</a>
        <span class="mx-1">/</span>
        <span class="text-neutral-300">Análisis</span>
    </nav>
@endsection

@push('pagination-links')
    @if ($articles->previousPageUrl())
        <link rel="prev" href="{{ $articles->previousPageUrl() }}">
    @endif
    @if ($articles->nextPageUrl())
        <link rel="next" href="{{ $articles->nextPageUrl() }}">
    @endif
@endpush

@push('json-ld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "Análisis - I-Licitaciones",
    "url": "{{ route('analisis.index') }}"
}
</script>
@endpush

@section('contenido')
    <div class="relative mb-10">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-cyan-500/5 to-transparent rounded-3xl blur-3xl"></div>
        <div class="relative">
            <h1 class="text-3xl md:text-5xl font-light mb-3 bg-gradient-to-r from-neutral-100 to-neutral-400 bg-clip-text text-transparent">
                Análisis
            </h1>
            <p class="text-neutral-400 text-sm md:text-base max-w-2xl">
                Periodismo de datos sobre la contratación pública en España. Rankings, alertas, informes y perfiles a partir de los datos oficiales.
            </p>
        </div>
    </div>

    @include('analisis._filtros', ['secciones' => $secciones, 'seccionActiva' => $seccionActiva])

    @if ($articles->isEmpty())
        <p class="text-neutral-500 italic">Todavía no hay análisis publicados.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($articles as $article)
                @include('analisis._card', ['article' => $article])
            @endforeach
        </div>

        <div class="mt-8">{{ $articles->links() }}</div>
    @endif
@endsection
