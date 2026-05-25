@php
    // spec: { title?, items: [{label, value, url?}], accent?, format? }
    $items = collect($spec['items'] ?? []);
    $accent = $spec['accent'] ?? 'emerald';
    $format = $spec['format'] ?? 'currency';
    $fmt = fn ($v) => $format === 'currency'
        ? number_format((float) $v, 0, ',', '.').'€'
        : number_format((float) $v, 0, ',', '.');
    $text = match ($accent) {
        'cyan' => 'text-cyan-400',
        'amber' => 'text-amber-400',
        'sky' => 'text-sky-400',
        default => 'text-emerald-400',
    };
@endphp
<figure class="my-8 not-prose">
    @isset($spec['title'])
        <figcaption class="text-sm text-neutral-400 mb-4">{{ $spec['title'] }}</figcaption>
    @endisset
    <div class="rounded-2xl border border-neutral-700/50 divide-y divide-neutral-800">
        @foreach ($items as $i => $item)
            <div class="flex items-center gap-3 px-4 py-3">
                <span class="w-6 text-neutral-500 text-sm font-mono">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="flex-1 text-neutral-300 truncate">
                    @isset($item['url'])
                        <a href="{{ $item['url'] }}" class="hover:text-white transition-colors">{{ $item['label'] ?? '' }}</a>
                    @else
                        {{ $item['label'] ?? '' }}
                    @endisset
                </span>
                <span class="font-mono text-sm {{ $text }}">{{ $fmt($item['value'] ?? 0) }}</span>
            </div>
        @endforeach
    </div>
</figure>
