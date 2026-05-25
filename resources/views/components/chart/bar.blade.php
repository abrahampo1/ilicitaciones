@php
    // spec: { title?, items: [{label, value}], max?, accent?, format? ('currency'|'number') }
    $items = collect($spec['items'] ?? []);
    $max = $spec['max'] ?? ($items->max('value') ?: 1);
    $accent = $spec['accent'] ?? 'emerald';
    $format = $spec['format'] ?? 'currency';
    $fmt = fn ($v) => $format === 'currency'
        ? number_format((float) $v, 0, ',', '.').'€'
        : number_format((float) $v, 0, ',', '.');
    $gradient = match ($accent) {
        'cyan' => 'from-sky-500 to-cyan-500',
        'amber' => 'from-amber-500 to-orange-500',
        'sky' => 'from-sky-500 to-blue-500',
        default => 'from-sky-500 to-emerald-500',
    };
@endphp
<figure class="my-8 not-prose">
    @isset($spec['title'])
        <figcaption class="text-sm text-neutral-400 mb-4">{{ $spec['title'] }}</figcaption>
    @endisset
    <div class="space-y-3">
        @foreach ($items as $item)
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-neutral-300 truncate pr-3">{{ $item['label'] ?? '' }}</span>
                    <span class="font-mono tabular-nums text-neutral-400 shrink-0">{{ $fmt($item['value'] ?? 0) }}</span>
                </div>
                <div class="h-1.5 w-full bg-neutral-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r {{ $gradient }} rounded-full"
                        style="width: {{ $max > 0 ? min(100, ((float) ($item['value'] ?? 0) / $max) * 100) : 0 }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</figure>
