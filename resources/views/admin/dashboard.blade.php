@extends('admin.layout')

@section('admin_title', 'Panel')

@section('admin_content')
    <h1 class="text-2xl font-light mb-6">Panel de redacción</h1>

    <div class="grid grid-cols-3 gap-4 mb-10">
        <a href="{{ route('admin.articles.index', ['status' => 'draft']) }}"
            class="p-5 rounded-2xl bg-neutral-800/50 border border-neutral-700/50 hover:border-neutral-600 transition-colors">
            <p class="text-xs uppercase tracking-wider text-neutral-400 mb-2">Borradores</p>
            <p class="text-3xl font-mono text-neutral-200">{{ $conteos['draft'] }}</p>
        </a>
        <a href="{{ route('admin.articles.index', ['status' => 'review']) }}"
            class="p-5 rounded-2xl bg-neutral-800/50 border border-neutral-700/50 hover:border-amber-500/40 transition-colors">
            <p class="text-xs uppercase tracking-wider text-neutral-400 mb-2">En revisión</p>
            <p class="text-3xl font-mono text-amber-400">{{ $conteos['review'] }}</p>
        </a>
        <a href="{{ route('admin.articles.index', ['status' => 'published']) }}"
            class="p-5 rounded-2xl bg-neutral-800/50 border border-neutral-700/50 hover:border-emerald-500/40 transition-colors">
            <p class="text-xs uppercase tracking-wider text-neutral-400 mb-2">Publicados</p>
            <p class="text-3xl font-mono text-emerald-400">{{ $conteos['published'] }}</p>
        </a>
    </div>

    <h2 class="text-lg font-light mb-4">Pendientes de revisión</h2>
    @if ($pendientes->isEmpty())
        <p class="text-sm text-neutral-500 italic">No hay borradores pendientes.</p>
    @else
        <div class="space-y-2">
            @foreach ($pendientes as $a)
                <a href="{{ route('admin.articles.edit', $a) }}"
                    class="flex items-center justify-between gap-4 p-3 rounded-xl bg-neutral-800/30 border border-neutral-700/30 hover:bg-neutral-800/60 transition-colors">
                    <span class="flex-1 truncate text-neutral-200">{{ $a->title }}</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $a->status->color() }}">{{ $a->status->label() }}</span>
                    <span class="text-xs text-neutral-500">{{ $a->updated_at->diffForHumans() }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
