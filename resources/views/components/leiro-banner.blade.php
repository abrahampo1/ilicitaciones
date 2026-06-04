{{-- Banner publicitario (cross-promo) de leiro.net: OMV de fibra y móvil
     para homelabbers, self-hosters y domótica open source.
     Variantes: 'horizontal' (por defecto) o 'compacto'. --}}
@props(['variant' => 'horizontal'])
@php
    $url = 'https://leiro.net/?utm_source=ilicitaciones&utm_medium=banner&utm_campaign=cross_promo';
@endphp

@if ($variant === 'compacto')
    <a href="{{ $url }}" target="_blank" rel="sponsored noopener noreferrer"
        class="group not-prose block rounded-xl border border-emerald-500/20 bg-neutral-900/60 px-4 py-3 transition-colors hover:border-emerald-500/40 hover:bg-neutral-800/60">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <span class="font-mono text-emerald-400 select-none">~$</span>
                <p class="truncate text-sm text-neutral-300">
                    <span class="font-medium text-neutral-100">Leiro.net</span> · Fibra y móvil sin CGNAT, IP fija e
                    IPv6 nativo para tu homelab
                </p>
            </div>
            <span
                class="shrink-0 font-mono text-xs text-emerald-400 group-hover:text-emerald-300">Apúntate&nbsp;→</span>
        </div>
    </a>
@else
    <a href="{{ $url }}" target="_blank" rel="sponsored noopener noreferrer" aria-label="Leiro.net: fibra y móvil para homelab (publicidad)"
        class="group not-prose relative block overflow-hidden rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-neutral-900 to-neutral-950 transition-colors hover:border-emerald-500/40">
        {{-- Resplandor decorativo --}}
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-emerald-500/10 blur-3xl"
            aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 left-1/3 h-48 w-48 rounded-full bg-cyan-500/10 blur-3xl"
            aria-hidden="true"></div>

        <div class="relative flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <p class="font-mono text-xs uppercase tracking-widest text-neutral-500">Publicidad</p>
                <p class="mt-3 font-mono text-sm text-neutral-400">
                    <span class="text-emerald-400">~$</span> ping
                    <span class="text-neutral-100">leiro.net</span>
                </p>
                <h3 class="mt-1 text-xl font-medium text-neutral-100 md:text-2xl">
                    El operador de fibra y móvil para tu
                    <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text font-semibold text-transparent">homelab</span>
                </h3>
                <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-2 font-mono text-xs text-neutral-400">
                    <li class="flex items-center gap-1.5"><span class="text-emerald-400">✓</span> Sin CGNAT, IP pública real</li>
                    <li class="flex items-center gap-1.5"><span class="text-emerald-400">✓</span> Fibra simétrica hasta 10&nbsp;Gb</li>
                    <li class="flex items-center gap-1.5"><span class="text-emerald-400">✓</span> IP fija e IPv6 /56 nativo</li>
                    <li class="flex items-center gap-1.5"><span class="text-emerald-400">✓</span> NAT abierto y VPN sin bloqueos</li>
                </ul>
            </div>

            <div class="flex shrink-0 flex-col items-start gap-2 md:items-end">
                <span
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-cyan-500 px-5 py-2.5 text-sm font-medium text-neutral-950 transition-transform group-hover:scale-[1.03]">
                    Únete a la lista de espera
                    <span aria-hidden="true">→</span>
                </span>
                <span class="font-mono text-xs text-neutral-500">leiro.net · precio de fundador</span>
            </div>
        </div>
    </a>
@endif
