<a href="{{ route('analisis.show', $article->slug) }}"
    class="group flex flex-col p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-xl hover:bg-neutral-800/60 hover:border-neutral-600/50 transition-all duration-300">
    <div class="flex items-center gap-2 mb-3">
        <span class="px-2 py-0.5 text-xs rounded-full {{ $article->section->color() }}">{{ $article->section->label() }}</span>
        <span class="text-xs text-neutral-500">
            {{ $article->published_at?->format('d/m/Y') }}
        </span>
    </div>
    <h3 class="font-light text-lg text-neutral-100 group-hover:text-white transition-colors line-clamp-2 mb-2">
        {{ $article->title }}
    </h3>
    @if ($article->dek)
        <p class="text-sm text-neutral-400 line-clamp-3 flex-1">{{ $article->dek }}</p>
    @endif
    <p class="text-xs text-neutral-500 mt-4">{{ $article->author_name ?? 'Redacción I-Licitaciones' }}</p>
</a>
