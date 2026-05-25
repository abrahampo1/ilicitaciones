<div class="flex flex-wrap gap-2 mb-8 text-sm">
    <a href="{{ route('analisis.index') }}"
        class="px-3 py-1.5 rounded-lg transition-colors {{ $seccionActiva === null ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50' }}">Todos</a>
    @foreach ($secciones as $s)
        <a href="{{ route('analisis.section', $s->value) }}"
            class="px-3 py-1.5 rounded-lg transition-colors {{ $seccionActiva === $s ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50' }}">{{ $s->label() }}</a>
    @endforeach
</div>
