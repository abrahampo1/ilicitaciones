@php
    // spec: { items: [{label, value, accent?}] }
    $items = collect($spec['items'] ?? []);
    $text = fn ($a) => match ($a) {
        'cyan' => 'text-cyan-400',
        'amber' => 'text-amber-400',
        'sky' => 'text-sky-400',
        'teal' => 'text-teal-400',
        default => 'text-emerald-400',
    };
    // Clases estáticas: Tailwind JIT no detecta clases construidas en runtime.
    $cols = match (min(4, max(1, $items->count()))) {
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        default => 'md:grid-cols-4',
    };
@endphp
<div class="my-8 not-prose grid grid-cols-2 {{ $cols }} gap-4">
    @foreach ($items as $item)
        <div class="p-5 bg-gradient-to-br from-neutral-800/80 to-neutral-900 border border-neutral-700/50 rounded-2xl">
            <p class="text-neutral-400 text-xs uppercase tracking-wider mb-2">{{ $item['label'] ?? '' }}</p>
            <p class="text-xl md:text-2xl font-mono {{ $text($item['accent'] ?? 'emerald') }}">{{ $item['value'] ?? '' }}</p>
        </div>
    @endforeach
</div>
