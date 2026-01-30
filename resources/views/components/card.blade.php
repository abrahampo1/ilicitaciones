@props(['titulo', 'valor', 'descripcion'])

<article class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
    <p class="text-xs text-neutral-300">{{ $titulo }}</p>
    <hr class="border-neutral-700">
    <p class="text-lg pt-2 text-neutral-100">{{ $valor }}</p>
    @if (isset($descripcion))
        <p class="text-xs mt-2 text-neutral-300">
            {{ $descripcion }}
        </p>
    @endif
</article>