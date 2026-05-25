@php
    // spec: { title?, text, tone? ('alerta'|'dato'|'nota') }
    $tone = $spec['tone'] ?? 'dato';
    [$border, $bg, $accent] = match ($tone) {
        'alerta' => ['border-amber-500/30', 'bg-amber-500/5', 'text-amber-400'],
        'nota' => ['border-neutral-600/40', 'bg-neutral-800/30', 'text-neutral-300'],
        default => ['border-cyan-500/30', 'bg-cyan-500/5', 'text-cyan-400'],
    };
@endphp
<aside class="my-8 not-prose border-l-2 {{ $border }} {{ $bg }} rounded-r-xl px-5 py-4">
    @isset($spec['title'])
        <p class="text-sm font-medium {{ $accent }} mb-1">{{ $spec['title'] }}</p>
    @endisset
    <p class="text-neutral-300 text-sm leading-relaxed">{{ $spec['text'] ?? '' }}</p>
</aside>
