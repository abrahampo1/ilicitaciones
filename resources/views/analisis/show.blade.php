@extends('layouts.app')

@section('meta_title', ($article->meta_title ?: $article->title).' | I-Licitaciones')
@section('meta_description', $article->meta_description ?: $article->dek)
@section('og_type', 'article')
@if ($article->og_image)
    @section('meta_image', $article->og_image)
@endif

@section('breadcrumbs')
    <nav aria-label="Migas de pan" class="text-xs text-neutral-500">
        <a href="{{ route('home') }}" class="hover:text-neutral-300">Inicio</a>
        <span class="mx-1">/</span>
        <a href="{{ route('analisis.index') }}" class="hover:text-neutral-300">Análisis</a>
        <span class="mx-1">/</span>
        <a href="{{ route('analisis.section', $article->section->value) }}" class="hover:text-neutral-300">{{ $article->section->label() }}</a>
        <span class="mx-1">/</span>
        <span class="text-neutral-300">{{ Str::limit($article->title, 40) }}</span>
    </nav>
@endsection

@push('styles')
<style>
    .article-prose { color: #d4d4d4; line-height: 1.75; }
    .article-prose > p { margin: 1.25rem 0; }
    .article-prose h2 { font-size: 1.5rem; font-weight: 300; margin: 2rem 0 1rem; color: #f5f5f5; }
    .article-prose h3 { font-size: 1.25rem; font-weight: 300; margin: 1.75rem 0 .75rem; color: #f5f5f5; }
    .article-prose ul, .article-prose ol { margin: 1.25rem 0; padding-left: 1.5rem; }
    .article-prose ul { list-style: disc; }
    .article-prose ol { list-style: decimal; }
    .article-prose li { margin: .35rem 0; }
    .article-prose a { color: #34d399; text-decoration: underline; text-underline-offset: 2px; }
    .article-prose blockquote { border-left: 2px solid #404040; padding-left: 1rem; color: #a3a3a3; font-style: italic; margin: 1.5rem 0; }
    .article-prose strong { color: #f5f5f5; }
</style>
@endpush

@push('json-ld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "NewsArticle",
    "headline": {!! json_encode(Str::limit($article->title, 110), JSON_UNESCAPED_UNICODE) !!},
    "description": {!! json_encode($article->dek ?? '', JSON_UNESCAPED_UNICODE) !!},
    "datePublished": "{{ $article->published_at?->toW3cString() }}",
    "dateModified": "{{ $article->updated_at?->toW3cString() }}",
    "articleSection": "{{ $article->section->label() }}",
    "author": { "@@type": "Organization", "name": {!! json_encode($article->author_name ?? 'Redacción I-Licitaciones', JSON_UNESCAPED_UNICODE) !!} },
    "publisher": { "@@type": "Organization", "name": "I-Licitaciones", "email": "{{ config('periodico.contacto') }}" },
    "mainEntityOfPage": "{{ route('analisis.show', $article->slug) }}"@if ($article->og_image),
    "image": "{{ $article->og_image }}"@endif
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Inicio", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Análisis", "item": "{{ route('analisis.index') }}" },
        { "@@type": "ListItem", "position": 3, "name": "{{ $article->section->label() }}", "item": "{{ route('analisis.section', $article->section->value) }}" },
        { "@@type": "ListItem", "position": 4, "name": {!! json_encode($article->title, JSON_UNESCAPED_UNICODE) !!} }
    ]
}
</script>
@endpush

@section('contenido')
    @if (($preview ?? false) || ! $article->isPublished())
        <div class="mb-6 px-4 py-3 rounded-xl bg-amber-500/10 text-amber-300 text-sm">
            Vista previa — este artículo ({{ $article->status->label() }}) no es visible para el público.
        </div>
    @endif

    <article class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2">
            <div class="relative mb-8">
                <div class="absolute -inset-x-4 -top-4 h-40 bg-gradient-to-r from-emerald-500/10 via-cyan-500/5 to-transparent rounded-3xl blur-2xl"></div>
                <div class="relative">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $article->section->color() }}">{{ $article->section->label() }}</span>
                    <h1 class="text-3xl md:text-4xl font-light mt-4 mb-4 text-neutral-100">{{ $article->title }}</h1>
                    @if ($article->dek)
                        <p class="text-lg text-neutral-400 font-light leading-relaxed mb-4">{{ $article->dek }}</p>
                    @endif
                    <p class="text-xs text-neutral-500">
                        {{ $article->author_name ?? 'Redacción I-Licitaciones' }}
                        @if ($article->published_at)
                            · {{ $article->published_at->format('d/m/Y') }}
                        @endif
                        @if ($article->updated_at && $article->published_at && $article->updated_at->gt($article->published_at))
                            · actualizado {{ $article->updated_at->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="article-prose max-w-none">
                {!! $bodyHtml !!}
            </div>
        </div>

        {{-- Raíl de entidades relacionadas --}}
        <aside class="space-y-6">
            @include('analisis._entidades', ['article' => $article])
        </aside>
    </article>

    <div class="mt-12">
        <a href="{{ route('analisis.index') }}" class="text-sm text-neutral-400 hover:text-emerald-400 transition-colors">&larr; Todos los análisis</a>
    </div>
@endsection
