@if (isset($analisis) && $analisis->isNotEmpty())
    <div class="bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 mt-6">
        <h3 class="flex items-center gap-2 text-neutral-300 text-sm font-medium mb-4">
            <span class="text-emerald-400">&#9998;</span> Mencionado en estos análisis
        </h3>
        <div class="space-y-2">
            @foreach ($analisis as $a)
                <a href="{{ route('analisis.show', $a->slug) }}"
                    class="block p-2 px-3 border border-neutral-800 rounded-lg hover:bg-neutral-800 transition-colors">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $a->section->color() }}">{{ $a->section->label() }}</span>
                    <span class="block mt-1 text-sm text-neutral-200">{{ $a->title }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
