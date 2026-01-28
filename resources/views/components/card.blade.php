@props(['titulo', 'valor', 'descripcion'])

<div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
    <p class="text-xs font-thin">{{ $titulo }}</p>
    <hr class="border-neutral-700">
    <p class="text-lg pt-2">{{ $valor }}</p>
    @if (isset($descripcion))
        <p class="text-xs mt-2">
            {{ $descripcion }}
        </p>

    @endif
</div>