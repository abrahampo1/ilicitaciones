@extends('admin.layout')

@section('admin_title', 'Artículos')

@section('admin_content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-light">Artículos</h1>
        <div class="flex gap-1 text-sm">
            <a href="{{ route('admin.articles.index') }}"
                class="px-3 py-1.5 rounded-lg {{ ! $status ? 'bg-neutral-800 text-white' : 'text-neutral-400 hover:bg-neutral-800/50' }}">Todos</a>
            @foreach (['draft' => 'Borradores', 'review' => 'Revisión', 'published' => 'Publicados'] as $val => $label)
                <a href="{{ route('admin.articles.index', ['status' => $val]) }}"
                    class="px-3 py-1.5 rounded-lg {{ $status === $val ? 'bg-neutral-800 text-white' : 'text-neutral-400 hover:bg-neutral-800/50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if ($articles->isEmpty())
        <p class="text-sm text-neutral-500 italic">No hay artículos.</p>
    @else
        <div class="space-y-2">
            @foreach ($articles as $a)
                <div class="flex items-center gap-4 p-3 rounded-xl bg-neutral-800/30 border border-neutral-700/30">
                    <a href="{{ route('admin.articles.edit', $a) }}" class="flex-1 min-w-0">
                        <p class="truncate text-neutral-200 hover:text-white transition-colors">{{ $a->title }}</p>
                        <p class="text-xs text-neutral-500">{{ $a->section->label() }}</p>
                    </a>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $a->status->color() }}">{{ $a->status->label() }}</span>
                    <a href="{{ route('admin.articles.preview', $a) }}"
                        class="text-xs text-neutral-400 hover:text-cyan-400 transition-colors">Vista previa</a>
                    @if ($a->isPublished())
                        <a href="{{ route('analisis.show', $a->slug) }}" target="_blank"
                            class="text-xs text-neutral-400 hover:text-emerald-400 transition-colors">Ver</a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $articles->links() }}</div>
    @endif
@endsection
